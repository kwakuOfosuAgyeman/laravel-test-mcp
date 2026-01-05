# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-05

### Added
- **generate_test** tool - Analyze Laravel classes and generate comprehensive test stubs
  - Supports Controllers, Models, Services, FormRequests, Jobs, Middleware, and Listeners
  - Automatic class type detection via inheritance and path conventions
  - Model relationship, scope, accessor, and mutator detection
  - Controller route and middleware detection
  - FormRequest validation rule extraction
  - Automatic factory generation for Eloquent models
  - Smart faker value generation based on field names and cast types

### New Components
- `ClassAnalyzer` service - PHP Reflection-based class analysis
- `TestGenerator` service - Pest test code generation with templates
- `FactoryGenerator` service - Laravel model factory generation
- `ClassAnalysis` DTO - Structured class analysis results
- `MethodInfo` DTO - Method signature information
- `GeneratedTest` DTO - Generated test and factory output

## [1.0.0] - 2025-01-01

### Added
- Initial release
- **run_tests** tool - Execute Pest/PHPUnit tests with filtering
- **list_tests** tool - Discover available tests
- **get_coverage** tool - Code coverage analysis
- **watch_tests** tool - TDD watch mode
- **mutation_test** tool - Mutation testing via Infection
- **cancel_operation** tool - Cancel long-running operations
- Progress tracking with operation IDs
- Rate limiting protection
- Confirmation threshold for large test suites
- MCP Resources for test results, coverage, config, and history
- MCP Prompts for TDD workflow, debugging, and coverage analysis
