---
name: quality-assurance
description: Guide for testing the current project. Use this when asked for running tests, checking static code analysis, fix code style issues and check for violations to the architecture guidelines.
---

When asked for QA or quality assurance, execute these CLI commands to ensure the baseline of quality is kept:

1. Use the `make qa` CLI command to run the entire quality assurance test suite
2. Use the `make test` CLI command to run only PHPUnit tests
3. Use the `make sa` CLI command to check of static code analysis issues
4. Use the `make cs` CLI command to fix code style issues
5. Use the `make arch` CLI command to check for architecture violations and warnings
6. Try to use quality assurance yourself after every major change you made to the codebase
7. Fix errors and failures yourself
