# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-05-23

### Added
- Initial release
- Pattern-based numbering with placeholders
- Roman numeral support (I, II, III, MMXXV)
- Roman date formats ({romanDate}, {romanDateShort}, {romanMonth}, {romanYear})
- Database counter via callback (framework agnostic)
- Auto reset period (daily, monthly, yearly)
- Fluent API with method chaining
- Template/preset system
- Case transformation (uppercase, lowercase)
- Custom transformation callbacks
- In-memory storage for testing
- Callback storage for custom implementations
- Full documentation with framework examples (Laravel, CodeIgniter, Plain PHP)
- SIMRS (Hospital Information System) use cases
- Complete test suite with PHPUnit

### Supported Patterns
- `{prefix}`, `{suffix}` - Static values
- `{sequence}`, `{sequence:N}` - Numeric sequence with padding
- `{roman}`, `{roman:N}` - Roman numeral sequence
- `{date}`, `{date:FORMAT}` - PHP date format
- `{year}`, `{month}`, `{day}` - Date components
- `{romanMonth}` - Month in Roman (I-XII)
- `{romanYear}`, `{romanYearShort}` - Year in Roman
- `{romanDate}`, `{romanDateShort}` - Full date in Roman
- `{param:NAME}` - Custom parameters
- `{random:N}` - Random alphanumeric
- Literal separators: `/`, `-`, etc.
