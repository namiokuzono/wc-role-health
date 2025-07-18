# Contributing Guidelines

Thank you for your interest in contributing to the WooCommerce Role Permission Health Check plugin! This document provides guidelines for contributing to the project.

## Getting Started

### Prerequisites

- **WordPress Development Environment**
  - Local WordPress installation
  - WooCommerce plugin installed
  - PHP 7.4 or higher
  - Git

- **Development Tools**
  - Code editor (VS Code, PHPStorm, etc.)
  - PHP_CodeSniffer (for coding standards)
  - WordPress Coding Standards

### Setting Up Development Environment

1. **Fork the Repository**
   ```bash
   git clone https://github.com/yourusername/woocommerce-role-permission-health-check.git
   cd woocommerce-role-permission-health-check
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Set Up WordPress Coding Standards**
   ```bash
   composer require --dev squizlabs/php_codesniffer
   composer require --dev wp-coding-standards/wpcs
   ```

4. **Configure PHP_CodeSniffer**
   ```bash
   phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs
   ```

## Development Guidelines

### Code Standards

#### PHP Coding Standards

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```php
/**
 * Example function following WordPress standards.
 *
 * @param string $param1 Description of parameter.
 * @param int    $param2 Description of parameter.
 * @return bool True on success, false on failure.
 */
function example_function( $param1, $param2 ) {
    if ( empty( $param1 ) ) {
        return false;
    }

    $result = do_something( $param1, $param2 );

    return $result;
}
```

#### JavaScript Coding Standards

Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/):

```javascript
/**
 * Example function following WordPress JavaScript standards.
 *
 * @param {string} param1 Description of parameter.
 * @param {number} param2 Description of parameter.
 * @return {boolean} True on success, false on failure.
 */
function exampleFunction( param1, param2 ) {
    if ( ! param1 ) {
        return false;
    }

    const result = doSomething( param1, param2 );

    return result;
}
```

#### CSS Coding Standards

Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/):

```css
/* Example CSS following WordPress standards */
.wc-rphc-example {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    font-size: 14px;
    line-height: 1.4;
    margin: 10px 0;
    padding: 15px;
}

.wc-rphc-example:hover {
    background: #f9f9f9;
}
```

### File Organization

#### Directory Structure

```
woocommerce-role-permission-health-check/
├── assets/
│   ├── admin.css
│   ├── admin.js
│   └── screenshots/
├── docs/
│   ├── INSTALLATION.md
│   ├── TROUBLESHOOTING.md
│   └── CONTRIBUTING.md
├── includes/
│   ├── class-health-checker.php
│   ├── class-emergency-recovery.php
│   └── class-admin-interface.php
├── languages/
│   └── wc-role-permission-health-check.pot
├── tests/
│   ├── bootstrap.php
│   ├── test-health-checker.php
│   └── test-emergency-recovery.php
├── .gitignore
├── composer.json
├── LICENSE
├── README.md
└── woocommerce-role-permission-health-check.php
```

#### Naming Conventions

- **Files:** Use kebab-case (e.g., `class-health-checker.php`)
- **Classes:** Use PascalCase with prefix (e.g., `WC_RPHC_Health_Checker`)
- **Functions:** Use snake_case with prefix (e.g., `wc_rphc_health_check`)
- **Constants:** Use UPPER_CASE with prefix (e.g., `WC_RPHC_VERSION`)
- **Variables:** Use snake_case (e.g., `$health_results`)

### Security Guidelines

#### Input Validation

Always validate and sanitize user input:

```php
/**
 * Validate and sanitize user input.
 *
 * @param string $input User input.
 * @return string Sanitized input.
 */
function wc_rphc_sanitize_input( $input ) {
    return sanitize_text_field( wp_unslash( $input ) );
}
```

#### Nonce Verification

Always verify nonces for security:

```php
/**
 * Verify nonce for AJAX requests.
 */
function wc_rphc_verify_nonce() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'wc_rphc_nonce' ) ) {
        wp_die( __( 'Security check failed.', 'wc-role-permission-health-check' ) );
    }
}
```

#### Capability Checks

Always check user capabilities:

```php
/**
 * Check if user can manage options.
 */
function wc_rphc_check_capability() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions.', 'wc-role-permission-health-check' ) );
    }
}
```

### Testing Guidelines

#### Unit Testing

Write unit tests for all functions:

```php
/**
 * Test health checker functionality.
 */
class Test_WC_RPHC_Health_Checker extends WP_UnitTestCase {

    /**
     * Test health check execution.
     */
    public function test_health_check_execution() {
        $health_checker = new WC_RPHC_Health_Checker();
        $results = $health_checker->run_health_check();

        $this->assertIsArray( $results );
        $this->assertNotEmpty( $results );
    }

    /**
     * Test capability checking.
     */
    public function test_capability_checking() {
        $health_checker = new WC_RPHC_Health_Checker();
        $result = $health_checker->check_woocommerce_capabilities();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'status', $result );
        $this->assertArrayHasKey( 'message', $result );
    }
}
```

#### Integration Testing

Test plugin integration with WordPress:

```php
/**
 * Test plugin activation.
 */
class Test_WC_RPHC_Integration extends WP_UnitTestCase {

    /**
     * Test plugin activation.
     */
    public function test_plugin_activation() {
        // Simulate plugin activation
        do_action( 'activate_woocommerce-role-permission-health-check/woocommerce-role-permission-health-check.php' );

        // Check if menu was added
        $this->assertTrue( has_action( 'admin_menu', array( 'WC_RPHC_Admin_Interface', 'add_admin_menu' ) ) );
    }
}
```

### Documentation Guidelines

#### Code Documentation

Document all functions, classes, and methods:

```php
/**
 * Health Checker Class
 * 
 * Handles all diagnostic and repair functionality for WooCommerce role and permission issues.
 * 
 * @package WC_Role_Permission_Health_Check
 * @since 1.0.0
 */
class WC_RPHC_Health_Checker {

    /**
     * Issues found during health check.
     *
     * @var array
     */
    private $issues_found = array();

    /**
     * Run comprehensive health check.
     *
     * @since 1.0.0
     * @return array Health check results.
     */
    public function run_health_check() {
        // Implementation
    }
}
```

#### README Updates

Update README.md for new features:

```markdown
## New Feature

### What it does
Brief description of the new feature.

### How to use
Step-by-step instructions.

### Configuration
Any configuration options.

### Examples
Code examples if applicable.
```

## Pull Request Process

### Before Submitting

1. **Run Tests**
   ```bash
   # Run PHP_CodeSniffer
   phpcs --standard=WordPress includes/ assets/

   # Run unit tests
   phpunit

   # Run integration tests
   phpunit --testsuite integration
   ```

2. **Check Compatibility**
   - Test with WordPress 5.0+
   - Test with WooCommerce 5.0+
   - Test with PHP 7.4+

3. **Update Documentation**
   - Update README.md if needed
   - Add inline documentation
   - Update changelog

### Pull Request Template

```markdown
## Description
Brief description of changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed
- [ ] Tested with WordPress 5.0+
- [ ] Tested with WooCommerce 5.0+

## Checklist
- [ ] Code follows WordPress coding standards
- [ ] Security best practices implemented
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] No breaking changes (or documented)

## Screenshots
If applicable, add screenshots.

## Related Issues
Closes #123
```

### Review Process

1. **Code Review**
   - All PRs require review
   - Address review comments
   - Update code as needed

2. **Testing**
   - Automated tests must pass
   - Manual testing may be required
   - Performance impact considered

3. **Documentation**
   - Code is well-documented
   - README updated if needed
   - Changelog updated

## Release Process

### Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **Major:** Breaking changes
- **Minor:** New features, backward compatible
- **Patch:** Bug fixes, backward compatible

### Release Checklist

- [ ] All tests pass
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] Version number updated
- [ ] Tagged release
- [ ] WordPress.org submission (if applicable)

## Community Guidelines

### Code of Conduct

- Be respectful and inclusive
- Help others learn
- Provide constructive feedback
- Follow WordPress community guidelines

### Communication

- Use GitHub issues for bugs
- Use GitHub discussions for questions
- Use GitHub PRs for contributions
- Be clear and concise

### Getting Help

- Check existing issues and discussions
- Search documentation
- Ask in discussions
- Contact maintainers if needed

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (GPL v2 or later).

## Recognition

Contributors will be recognized in:

- README.md contributors section
- Release notes
- Plugin credits
- GitHub contributors page

Thank you for contributing to the WooCommerce Role Permission Health Check plugin! 