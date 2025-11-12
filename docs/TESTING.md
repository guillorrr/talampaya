# Testing

Complete guide to testing in Talampaya, including PHP and JavaScript test configuration and execution.

## Table of Contents

- [Testing](#testing)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [PHP Testing](#php-testing)
    - [Configuration](#configuration)
    - [Running PHP Tests](#running-php-tests)
    - [Writing PHP Tests](#writing-php-tests)
    - [Test Structure](#test-structure)
  - [JavaScript Testing](#javascript-testing)
    - [Configuration](#configuration-1)
    - [Running JavaScript Tests](#running-javascript-tests)
    - [Writing JavaScript Tests](#writing-javascript-tests)
  - [Test Organization](#test-organization)
  - [Best Practices](#best-practices)
  - [Continuous Integration](#continuous-integration)

## Overview

Talampaya includes testing infrastructure for both PHP and JavaScript:

| Type | Framework | Location | Command |
|------|-----------|----------|---------|
| **PHP** | PHPUnit + WordBless | `/src/theme/tests/` | `npm test` or `composer test` |
| **JavaScript** | Jest | `/tests/` (root) | `npm run test:js` |

## PHP Testing

### Configuration

**Framework**: PHPUnit with WordBless (WordPress testing library)

**Configuration file**: `/src/theme/phpunit.xml`

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true">
    <testsuites>
        <testsuite name="Talampaya Test Suite">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Running PHP Tests

```bash
# Via npm
npm test

# Via Composer
composer test

# Via Docker (inside wp container)
docker compose exec wp vendor/bin/phpunit -c wp-content/themes/talampaya
```

### Writing PHP Tests

**Location**: `/src/theme/tests/test-*.php`

**Example**: `/src/theme/tests/test-post-helper.php`

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Inc\Helpers\PostHelper;

class PostHelperTest extends TestCase
{
    public function test_get_excerpt_returns_string()
    {
        $excerpt = PostHelper::getExcerpt('This is a test post content', 10);

        $this->assertIsString($excerpt);
        $this->assertLessThanOrEqual(10, str_word_count($excerpt));
    }

    public function test_get_excerpt_with_empty_content()
    {
        $excerpt = PostHelper::getExcerpt('', 10);

        $this->assertEmpty($excerpt);
    }
}
```

### Test Structure

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    // Setup before each test
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize test data
    }

    // Cleanup after each test
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test data
    }

    // Test method (must start with 'test')
    public function test_something_works()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = someFunction($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

**Common assertions**:
```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict comparison

// Types
$this->assertIsString($value);
$this->assertIsArray($value);
$this->assertIsBool($value);

// Truthiness
$this->assertTrue($value);
$this->assertFalse($value);
$this->assertNull($value);

// Empty/Not Empty
$this->assertEmpty($value);
$this->assertNotEmpty($value);

// Strings
$this->assertStringContainsString('needle', $haystack);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Arrays
$this->assertArrayHasKey('key', $array);
$this->assertContains('value', $array);
```

## JavaScript Testing

### Configuration

**Framework**: Jest

**Configuration file**: `/jest.config.js`

```javascript
module.exports = {
  testEnvironment: 'jsdom',
  testMatch: ['**/tests/**/*.test.js'],
  collectCoverageFrom: [
    'src/theme/assets/scripts/**/*.js',
    '!src/theme/assets/scripts/vendor/**'
  ],
  coverageDirectory: 'coverage',
  moduleFileExtensions: ['js', 'json'],
  transform: {
    '^.+\\.js$': 'babel-jest'
  }
};
```

### Running JavaScript Tests

```bash
# Run all tests
npm run test:js

# Run with coverage
npm run test:js -- --coverage

# Run in watch mode
npm run test:js -- --watch

# Run specific test file
npm run test:js -- tests/mytest.test.js
```

### Writing JavaScript Tests

**Location**: `/tests/*.test.js`

**Example**: `/tests/utils.test.js`

```javascript
import { formatPrice, slugify } from '../src/theme/assets/scripts/utils';

describe('Utils', () => {
  describe('formatPrice', () => {
    test('formats price with currency symbol', () => {
      const result = formatPrice(1234.56);
      expect(result).toBe('$1,234.56');
    });

    test('handles zero price', () => {
      const result = formatPrice(0);
      expect(result).toBe('$0.00');
    });
  });

  describe('slugify', () => {
    test('converts string to slug', () => {
      const result = slugify('Hello World');
      expect(result).toBe('hello-world');
    });

    test('removes special characters', () => {
      const result = slugify('Hello! @World#');
      expect(result).toBe('hello-world');
    });
  });
});
```

**Test structure**:
```javascript
describe('Component/Module Name', () => {
  // Setup before all tests in this describe block
  beforeAll(() => {
    // One-time setup
  });

  // Setup before each test
  beforeEach(() => {
    // Reset state
  });

  // Cleanup after each test
  afterEach(() => {
    // Clean up
  });

  // Cleanup after all tests
  afterAll(() => {
    // One-time cleanup
  });

  test('should do something', () => {
    // Arrange
    const input = 'test';

    // Act
    const result = myFunction(input);

    // Assert
    expect(result).toBe('expected');
  });
});
```

**Common Jest matchers**:
```javascript
// Equality
expect(value).toBe(expected);       // ===
expect(value).toEqual(expected);    // Deep equality

// Truthiness
expect(value).toBeTruthy();
expect(value).toBeFalsy();
expect(value).toBeNull();
expect(value).toBeUndefined();
expect(value).toBeDefined();

// Numbers
expect(value).toBeGreaterThan(3);
expect(value).toBeLessThan(5);
expect(value).toBeCloseTo(0.3, 5); // Floating point

// Strings
expect(string).toMatch(/pattern/);
expect(string).toContain('substring');

// Arrays/Iterables
expect(array).toContain(item);
expect(array).toHaveLength(3);

// Objects
expect(object).toHaveProperty('key');
expect(object).toMatchObject({ key: 'value' });

// Functions
expect(fn).toThrow();
expect(fn).toHaveBeenCalled();
expect(fn).toHaveBeenCalledWith('arg1', 'arg2');
```

## Test Organization

```
/src/theme/tests/              # PHP tests
  ├── bootstrap.php            # Test bootstrap
  ├── test-post-helper.php     # Helper tests
  ├── test-file-utils.php      # Utility tests
  └── test-content-generator.php

/tests/                        # JavaScript tests
  ├── utils.test.js
  ├── components.test.js
  └── modules.test.js
```

**Naming conventions**:
- PHP: `test-{class-name}.php`
- JavaScript: `{module-name}.test.js`

## Best Practices

1. **Follow AAA pattern** (Arrange, Act, Assert):
   ```php
   // Arrange - Set up test data
   $input = 'test data';

   // Act - Execute the function
   $result = myFunction($input);

   // Assert - Verify the result
   $this->assertEquals('expected', $result);
   ```

2. **Test one thing per test**:
   ```php
   // Good
   public function test_get_post_returns_post_object()
   {
       $post = getPost(1);
       $this->assertInstanceOf(Post::class, $post);
   }

   // Bad (testing multiple things)
   public function test_get_post()
   {
       $post = getPost(1);
       $this->assertInstanceOf(Post::class, $post);
       $this->assertEquals('Title', $post->title);
       $this->assertTrue($post->isPublished());
   }
   ```

3. **Use descriptive test names**:
   ```php
   // Good
   public function test_get_excerpt_truncates_long_content()

   // Bad
   public function test_excerpt()
   ```

4. **Mock external dependencies**:
   ```javascript
   jest.mock('../api/posts', () => ({
     fetchPosts: jest.fn(() => Promise.resolve([]))
   }));
   ```

5. **Test edge cases**:
   - Empty inputs
   - Null/undefined values
   - Large numbers
   - Special characters

6. **Aim for high coverage**, but don't obsess:
   - Critical business logic: 100%
   - Utilities and helpers: 80-90%
   - Simple getters/setters: Optional

## Continuous Integration

**Example GitHub Actions workflow** (`.github/workflows/tests.yml`):

```yaml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: composer test

  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: '18'
      - name: Install dependencies
        run: npm ci
      - name: Run tests
        run: npm run test:js
```

---

For related documentation:
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
- [CONTRIBUTING.md](CONTRIBUTING.md) - Code standards and guidelines