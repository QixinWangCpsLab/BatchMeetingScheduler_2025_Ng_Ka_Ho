# Batch Meeting Scheduler Documentation

This directory is the main documentation hub for future student developers, instructors, and students.

## Start Here

- Developers: read [Developer Setup](./developer-setup.md), then [System Architecture](./system-architecture.md), then [Maintenance Guide](./maintenance-guide.md).
- Instructors: read [Instructor Manual](./instructor-manual.md).
- Students: read [Student Manual](./student-manual.md).

## Quick Facts

- Primary supported environment: Docker Compose from `src/`
- Web application: PHP 8.0 + Apache
- Database: MySQL 8.0
- Database bootstrap file: `src/db/capstone_project.sql`
- Main entry page after startup: `http://localhost:5000/index.html`
- Database port mapping in Docker: `localhost:50000`

## Documentation Map

- [Developer Setup](./developer-setup.md)
  Docker-first local setup, environment variables, startup steps, and troubleshooting.
- [System Architecture](./system-architecture.md)
  Runtime stack, subsystem overview, data model, and request flow.
- [Maintenance Guide](./maintenance-guide.md)
  Safe extension points, fragile areas, and maintenance checklist.
- [Instructor Manual](./instructor-manual.md)
  End-user instructions for teachers.
- [Student Manual](./student-manual.md)
  End-user instructions for students.

## Recommended Reading Order for New Maintainers

1. Bring the system up with [Developer Setup](./developer-setup.md).
2. Trace the system structure with [System Architecture](./system-architecture.md).
3. Review extension and risk notes in [Maintenance Guide](./maintenance-guide.md).
4. Use the role manuals to validate that the documented user journeys still match the UI.

## Source Materials Already in This Repository

- Legacy project notes: `src/README.md`
- Database folder note: `src/db/README.md`
- Web folder note: `src/web/README.md`
- Archived project reports and slides: PDF files in `docs/`
