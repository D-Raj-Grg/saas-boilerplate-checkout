# Testing Guide

This project uses **Jest** and **React Testing Library** for testing.

## Running Tests

```bash
# Run all tests
pnpm test

# Run tests in watch mode (useful during development)
pnpm test:watch

# Run tests with coverage report
pnpm test:coverage
```

## Test Structure

Tests are organized in the `__tests__` directory:

```
__tests__/
├── components/        # Component tests
│   └── ui/
│       └── button.test.tsx
├── lib/              # Utility function tests
│   └── utils.test.ts
└── example.test.ts   # Example tests (can be deleted)
```

## Writing Tests

### Component Tests

```tsx
import { render, screen } from '@testing-library/react'
import { YourComponent } from '@/components/your-component'
import userEvent from '@testing-library/user-event'

describe('YourComponent', () => {
  it('renders correctly', () => {
    render(<YourComponent />)
    expect(screen.getByText('Expected Text')).toBeInTheDocument()
  })

  it('handles user interactions', async () => {
    const user = userEvent.setup()
    const handleClick = jest.fn()

    render(<YourComponent onClick={handleClick} />)
    await user.click(screen.getByRole('button'))

    expect(handleClick).toHaveBeenCalled()
  })
})
```

### Unit Tests

```ts
import { yourFunction } from '@/lib/your-module'

describe('yourFunction', () => {
  it('returns expected result', () => {
    expect(yourFunction('input')).toBe('expected output')
  })
})
```

## Best Practices

1. **Test user behavior, not implementation details**
2. **Use semantic queries** (`getByRole`, `getByLabelText`, etc.)
3. **Keep tests simple and focused**
4. **Mock external dependencies** (API calls, etc.)
5. **Test edge cases and error scenarios**

## Coverage

Run `pnpm test:coverage` to generate a coverage report. The report will be available in the `coverage/` directory.

Aim for:
- **80%+ statement coverage**
- **80%+ branch coverage**
- **Critical paths should have 100% coverage**

## Useful Links

- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/)
- [Testing Library Queries](https://testing-library.com/docs/queries/about)
- [User Event](https://testing-library.com/docs/user-event/intro)
