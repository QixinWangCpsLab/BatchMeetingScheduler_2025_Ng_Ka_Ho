<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Hong_Kong');

final class IntegrationTestSuite
{
    private mysqli $db;
    /** @var string[] */
    private array $createdExamIds = [];
    private string $webRoot;
    private string $runnerPath;

    public function __construct()
    {
        $this->webRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $this->runnerPath = $this->webRoot . '/tests/support/request_runner.php';
        $this->db = $this->createDbConnection();
    }

    public function run(): int
    {
        $tests = [
            'meeting creation stores the meeting, slots, and student roster' => fn() => $this->testMeetingCreation(),
            'validation rejects duplicate slot selections' => fn() => $this->testValidationRejectsDuplicateSelections(),
            'allocation assigns students into available slots without duplicates' => fn() => $this->testAllocationWorks(),
            'concurrent allocation requests do not double book slots or students' => fn() => $this->testConcurrentAllocationAvoidsDoubleBooking(),
        ];

        $failures = 0;

        try {
            foreach ($tests as $name => $test) {
                try {
                    $test();
                    $this->writeLine("PASS {$name}");
                } catch (Throwable $throwable) {
                    $failures++;
                    $this->writeLine("FAIL {$name}");
                    $this->writeLine("  {$throwable->getMessage()}");
                }
            }
        } finally {
            $this->cleanupCreatedMeetings();
            $this->db->close();
        }

        $total = count($tests);
        $passed = $total - $failures;
        $this->writeLine("Summary: {$passed}/{$total} passed");

        return $failures === 0 ? 0 : 1;
    }

    private function testMeetingCreation(): void
    {
        $meeting = $this->createMeeting(
            ['S10000001', 'S10000002', 'S10000003'],
            [
                'titlePrefix' => 'Functional creation',
                'daycount' => 1,
                'Datechoicenum' => 1,
                'Slotchoicenum' => 2,
                'duration' => 30,
                'days' => [
                    [
                        'date' => date('Y-m-d', strtotime('+2 days')),
                        'ranges' => [['09:00', '10:00']],
                    ],
                ],
            ]
        );

        $examCount = (int) $this->fetchValue('SELECT COUNT(*) FROM exam WHERE examid = ?', 's', [$meeting['examid']]);
        $dateCount = (int) $this->fetchValue('SELECT COUNT(*) FROM MeetingDate WHERE examid = ?', 's', [$meeting['examid']]);
        $slotCount = (int) $this->fetchValue('SELECT COUNT(*) FROM meetingtimeslots WHERE examid = ?', 's', [$meeting['examid']]);
        $studentCount = (int) $this->fetchValue('SELECT COUNT(*) FROM studentexammatch WHERE examid = ?', 's', [$meeting['examid']]);

        $this->assertSame(1, $examCount, 'Meeting row was not created.');
        $this->assertSame(1, $dateCount, 'Meeting date row count is unexpected.');
        $this->assertSame(2, $slotCount, 'Time slot count is unexpected.');
        $this->assertSame(3, $studentCount, 'Student roster count is unexpected.');
    }

    private function testValidationRejectsDuplicateSelections(): void
    {
        $meeting = $this->createMeeting(
            ['S20000001'],
            [
                'titlePrefix' => 'Validation duplicate',
                'Datechoicenum' => 1,
                'Slotchoicenum' => 2,
                'duration' => 30,
                'days' => [
                    [
                        'date' => date('Y-m-d', strtotime('+3 days')),
                        'ranges' => [['13:00', '14:00']],
                    ],
                ],
            ]
        );

        $studentId = array_key_first($meeting['studentPasswords']);
        $slotIds = array_column($meeting['slots'], 'timeslotid');

        $response = $this->runRequest([
            'script' => 'chooseform.php',
            'method' => 'POST',
            'post' => [
                'examid' => $meeting['examid'],
                'studentid' => $studentId,
                'stupassword' => $meeting['studentPasswords'][$studentId],
                'timestamp' => '100',
                'choose1' => $slotIds[0],
                'choose2' => $slotIds[0],
            ],
        ]);

        $this->assertContains('Duplicate time-slot selections detected', $response['output'], 'Duplicate-choice validation message was not returned.');

        $preferenceCount = (int) $this->fetchValue(
            'SELECT COUNT(*) FROM preference WHERE examid = ? AND studentid = ?',
            'ss',
            [$meeting['examid'], $studentId]
        );
        $this->assertSame(0, $preferenceCount, 'Duplicate submission should not be persisted.');
    }

    private function testAllocationWorks(): void
    {
        $meeting = $this->createMeeting(
            ['S30000001', 'S30000002'],
            [
                'titlePrefix' => 'Allocation functional',
                'Datechoicenum' => 1,
                'Slotchoicenum' => 2,
                'duration' => 30,
                'days' => [
                    [
                        'date' => date('Y-m-d', strtotime('+4 days')),
                        'ranges' => [['15:00', '16:00']],
                    ],
                ],
            ]
        );

        $slotIds = array_column($meeting['slots'], 'timeslotid');
        $students = array_keys($meeting['studentPasswords']);

        $this->submitPreferences($meeting, $students[0], [$slotIds[0], $slotIds[1]], 100);
        $this->submitPreferences($meeting, $students[1], [$slotIds[0], $slotIds[1]], 200);
        $this->setMeetingDeadlineToPast($meeting['examid']);

        $response = $this->runRequest([
            'script' => 'result.php',
            'method' => 'GET',
            'get' => [
                'examid' => $meeting['examid'],
                'Tpassword' => $meeting['teacherPassword'],
            ],
        ]);

        $this->assertContains($students[0], $response['output'], 'Allocated result page did not include the first student.');
        $this->assertContains($students[1], $response['output'], 'Allocated result page did not include the second student.');

        $assignedCount = (int) $this->fetchValue('SELECT COUNT(*) FROM result WHERE examid = ?', 's', [$meeting['examid']]);
        $distinctStudents = (int) $this->fetchValue('SELECT COUNT(DISTINCT studentid) FROM result WHERE examid = ?', 's', [$meeting['examid']]);
        $distinctSlots = (int) $this->fetchValue('SELECT COUNT(DISTINCT timeslotid) FROM result WHERE examid = ?', 's', [$meeting['examid']]);

        $this->assertSame(2, $assignedCount, 'Expected both students to receive a slot.');
        $this->assertSame(2, $distinctStudents, 'Expected both students to be allocated exactly once.');
        $this->assertSame(2, $distinctSlots, 'Expected both available slots to be used exactly once.');
    }

    private function testConcurrentAllocationAvoidsDoubleBooking(): void
    {
        $meeting = $this->createMeeting(
            ['S40000001', 'S40000002', 'S40000003', 'S40000004'],
            [
                'titlePrefix' => 'Concurrency allocation',
                'Datechoicenum' => 1,
                'Slotchoicenum' => 2,
                'duration' => 30,
                'days' => [
                    [
                        'date' => date('Y-m-d', strtotime('+5 days')),
                        'ranges' => [['09:00', '10:00']],
                    ],
                ],
            ]
        );

        $slotIds = array_column($meeting['slots'], 'timeslotid');
        $timestamp = 100;
        foreach (array_keys($meeting['studentPasswords']) as $studentId) {
            $this->submitPreferences($meeting, $studentId, [$slotIds[0], $slotIds[1]], $timestamp);
            $timestamp += 10;
        }

        $this->setMeetingDeadlineToPast($meeting['examid']);

        $requests = [];
        for ($i = 0; $i < 5; $i++) {
            $requests[] = [
                'script' => 'result.php',
                'method' => 'GET',
                'get' => [
                    'examid' => $meeting['examid'],
                    'Tpassword' => $meeting['teacherPassword'],
                ],
            ];
        }

        $responses = $this->runRequestsConcurrently($requests);
        foreach ($responses as $index => $response) {
            if (($response['fatalError'] ?? null) !== null) {
                throw new RuntimeException('Concurrent allocation worker #' . ($index + 1) . ' failed: ' . $response['fatalError']);
            }
        }

        $assignedCount = (int) $this->fetchValue('SELECT COUNT(*) FROM result WHERE examid = ?', 's', [$meeting['examid']]);
        $distinctSlots = (int) $this->fetchValue('SELECT COUNT(DISTINCT timeslotid) FROM result WHERE examid = ?', 's', [$meeting['examid']]);
        $distinctStudents = (int) $this->fetchValue('SELECT COUNT(DISTINCT studentid) FROM result WHERE examid = ?', 's', [$meeting['examid']]);
        $duplicateSlots = (int) $this->fetchValue(
            'SELECT COUNT(*) FROM (SELECT timeslotid FROM result WHERE examid = ? GROUP BY timeslotid HAVING COUNT(*) > 1) AS duplicated_slots',
            's',
            [$meeting['examid']]
        );
        $duplicateStudents = (int) $this->fetchValue(
            'SELECT COUNT(*) FROM (SELECT studentid FROM result WHERE examid = ? GROUP BY studentid HAVING COUNT(*) > 1) AS duplicated_students',
            's',
            [$meeting['examid']]
        );

        $this->assertSame(2, $assignedCount, 'Expected exactly one allocation per available slot.');
        $this->assertSame($assignedCount, $distinctSlots, 'Detected duplicate slot assignments.');
        $this->assertSame($assignedCount, $distinctStudents, 'Detected duplicate student assignments.');
        $this->assertSame(0, $duplicateSlots, 'A time slot was double booked.');
        $this->assertSame(0, $duplicateStudents, 'A student was allocated more than once.');
    }

    /**
     * @param string[] $studentIds
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function createMeeting(array $studentIds, array $options = []): array
    {
        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $title = ($options['titlePrefix'] ?? 'Integration meeting') . ' ' . $suffix;
        $days = $options['days'] ?? [
            [
                'date' => date('Y-m-d', strtotime('+2 days')),
                'ranges' => [['09:00', '10:00']],
            ],
        ];

        $post = [
            'title' => $title,
            'subject' => $options['subject'] ?? 'COMP4913',
            'teacher' => $options['teacher'] ?? 'Integration Tester',
            'duration' => (string) ($options['duration'] ?? 30),
            'deadline' => $options['deadline'] ?? date('Y-m-d H:i:s', strtotime('+1 day')),
            'Datechoicenum' => (string) ($options['Datechoicenum'] ?? 1),
            'Slotchoicenum' => (string) ($options['Slotchoicenum'] ?? 2),
            'daycount' => (string) ($options['daycount'] ?? count($days)),
        ];

        foreach ($days as $index => $day) {
            $dayNumber = $index + 1;
            $post["day{$dayNumber}date"] = $day['date'];
            $post["day{$dayNumber}startime"] = array_column($day['ranges'], 0);
            $post["day{$dayNumber}endtime"] = array_column($day['ranges'], 1);
        }

        $csvPath = tempnam(sys_get_temp_dir(), 'meeting_students_');
        if ($csvPath === false) {
            throw new RuntimeException('Unable to create temporary CSV file.');
        }

        $csvContent = "studentid\n" . implode("\n", $studentIds) . "\n";
        file_put_contents($csvPath, $csvContent);

        try {
            $response = $this->runRequest([
                'script' => 'createmeeting.php',
                'method' => 'POST',
                'post' => $post,
                'files' => [
                    'importfile' => [
                        'name' => 'students.csv',
                        'type' => 'text/csv',
                        'tmp_name' => $csvPath,
                        'error' => UPLOAD_ERR_OK,
                        'size' => filesize($csvPath),
                    ],
                ],
            ]);
        } finally {
            @unlink($csvPath);
        }

        if (($response['fatalError'] ?? null) !== null) {
            throw new RuntimeException('Meeting creation failed: ' . $response['fatalError']);
        }

        $meetingRow = $this->fetchRow(
            'SELECT examid, password, datechoicenum, slotchoicenum FROM exam WHERE title = ?',
            's',
            [$title]
        );

        if ($meetingRow === null) {
            throw new RuntimeException('Meeting creation did not persist the exam row. Response: ' . trim((string) ($response['output'] ?? '')));
        }

        $examId = $meetingRow['examid'];
        $this->createdExamIds[] = $examId;

        $studentRows = $this->fetchAll(
            'SELECT studentid, password FROM studentexammatch WHERE examid = ? ORDER BY studentid ASC',
            's',
            [$examId]
        );
        $studentPasswords = [];
        foreach ($studentRows as $row) {
            $studentPasswords[$row['studentid']] = $row['password'];
        }

        return [
            'examid' => $examId,
            'teacherPassword' => $meetingRow['password'],
            'dateChoiceNum' => (int) $meetingRow['datechoicenum'],
            'slotChoiceNum' => (int) $meetingRow['slotchoicenum'],
            'studentPasswords' => $studentPasswords,
            'slots' => $this->fetchAll(
                'SELECT timeslotid, timeslot, dateid FROM meetingtimeslots WHERE examid = ? ORDER BY timeslotid ASC',
                's',
                [$examId]
            ),
        ];
    }

    /**
     * @param array<string, mixed> $meeting
     * @param int[] $slotIds
     */
    private function submitPreferences(array $meeting, string $studentId, array $slotIds, int $timestamp): void
    {
        $post = [
            'examid' => $meeting['examid'],
            'studentid' => $studentId,
            'stupassword' => $meeting['studentPasswords'][$studentId],
            'timestamp' => (string) $timestamp,
        ];

        foreach ($slotIds as $index => $slotId) {
            $post['choose' . ($index + 1)] = $slotId;
        }

        $response = $this->runRequest([
            'script' => 'chooseform.php',
            'method' => 'POST',
            'post' => $post,
        ]);

        if (($response['fatalError'] ?? null) !== null) {
            throw new RuntimeException('Preference submission failed for ' . $studentId . ': ' . $response['fatalError']);
        }

        $savedCount = (int) $this->fetchValue(
            'SELECT COUNT(*) FROM preference WHERE examid = ? AND studentid = ?',
            'ss',
            [$meeting['examid'], $studentId]
        );

        $this->assertSame(count($slotIds), $savedCount, 'Preference submission was not persisted correctly for ' . $studentId . '.');
    }

    private function setMeetingDeadlineToPast(string $examId): void
    {
        $stmt = $this->db->prepare('UPDATE exam SET deadline = ? WHERE examid = ?');
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare deadline update: ' . $this->db->error);
        }

        $pastDeadline = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $stmt->bind_param('ss', $pastDeadline, $examId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param array<string, mixed> $spec
     * @return array<string, mixed>
     */
    private function runRequest(array $spec): array
    {
        $responses = $this->runRequestsConcurrently([$spec]);
        return $responses[0];
    }

    /**
     * @param array<int, array<string, mixed>> $specs
     * @return array<int, array<string, mixed>>
     */
    private function runRequestsConcurrently(array $specs): array
    {
        $processes = [];
        $responses = [];
        $environment = $this->buildChildEnvironment();

        foreach ($specs as $index => $spec) {
            $specPath = tempnam(sys_get_temp_dir(), 'request_spec_');
            if ($specPath === false) {
                throw new RuntimeException('Unable to create temporary request file.');
            }

            file_put_contents($specPath, json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            $command = 'php ' . escapeshellarg($this->runnerPath) . ' ' . escapeshellarg($specPath);
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptors, $pipes, $this->webRoot, $environment);

            if (!is_resource($process)) {
                @unlink($specPath);
                throw new RuntimeException('Unable to start PHP request runner.');
            }

            fclose($pipes[0]);
            $processes[$index] = [
                'process' => $process,
                'stdout' => $pipes[1],
                'stderr' => $pipes[2],
                'specPath' => $specPath,
            ];
        }

        foreach ($processes as $index => $processInfo) {
            $stdout = stream_get_contents($processInfo['stdout']);
            $stderr = stream_get_contents($processInfo['stderr']);
            fclose($processInfo['stdout']);
            fclose($processInfo['stderr']);

            $exitCode = proc_close($processInfo['process']);
            @unlink($processInfo['specPath']);

            if ($exitCode !== 0) {
                throw new RuntimeException('Request runner failed with exit code ' . $exitCode . ': ' . trim($stderr));
            }

            $decoded = json_decode($stdout, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Request runner returned invalid JSON: ' . trim($stdout) . ' ' . trim($stderr));
            }

            $responses[$index] = $decoded;
        }

        ksort($responses);
        return array_values($responses);
    }

    /**
     * @return array<string, string>|null
     */
    private function buildChildEnvironment(): ?array
    {
        $environment = [];
        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $environment[$key] = $value;
            }
        }

        return $environment === [] ? null : $environment;
    }

    /**
     * @param array<int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql, string $types, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $this->db->error);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    private function fetchRow(string $sql, string $types, array $params): ?array
    {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function fetchValue(string $sql, string $types, array $params): mixed
    {
        $row = $this->fetchRow($sql, $types, $params);
        if ($row === null) {
            return null;
        }

        return array_values($row)[0];
    }

    private function cleanupCreatedMeetings(): void
    {
        $examIds = array_values(array_unique($this->createdExamIds));
        foreach ($examIds as $examId) {
            foreach (['result', 'preference', 'studentexammatch', 'meetingtimeslots', 'MeetingDate', 'exam'] as $table) {
                $stmt = $this->db->prepare("DELETE FROM {$table} WHERE examid = ?");
                if ($stmt === false) {
                    continue;
                }
                $stmt->bind_param('s', $examId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    private function createDbConnection(): mysqli
    {
        $config = require $this->webRoot . '/config.php';
        $dbConfig = $config['db'];

        mysqli_report(MYSQLI_REPORT_OFF);
        $db = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);

        if ($db->connect_error) {
            throw new RuntimeException(
                'Database connection failed. Set DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD for the current database. ' .
                $db->connect_error
            );
        }

        $db->set_charset('utf8mb4');
        return $db;
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new RuntimeException($message . ' Missing text: ' . $needle);
        }
    }

    private function writeLine(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}

try {
    $suite = new IntegrationTestSuite();
    exit($suite->run());
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
