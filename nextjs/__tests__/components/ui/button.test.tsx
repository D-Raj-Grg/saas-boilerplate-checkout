import { render, screen } from '@testing-library/react'
import { Button } from '@/components/ui/button'
import userEvent from '@testing-library/user-event'

describe('Button Component', () => {
  it('renders button with text', () => {
    render(<Button>Click me</Button>)
    expect(screen.getByText('Click me')).toBeInTheDocument()
  })

  it('handles click events', async () => {
    const handleClick = jest.fn()
    const user = userEvent.setup()

    render(<Button onClick={handleClick}>Click me</Button>)

    await user.click(screen.getByText('Click me'))
    expect(handleClick).toHaveBeenCalledTimes(1)
  })

  it('applies variant styles correctly', () => {
    const { container } = render(<Button variant="destructive">Delete</Button>)
    const button = container.querySelector('button')
    expect(button).toHaveClass('bg-destructive')
  })

  it('renders as disabled when disabled prop is true', () => {
    render(<Button disabled>Disabled Button</Button>)
    expect(screen.getByText('Disabled Button')).toBeDisabled()
  })

  it('does not trigger onClick when disabled', async () => {
    const handleClick = jest.fn()
    const user = userEvent.setup()

    render(<Button disabled onClick={handleClick}>Disabled</Button>)

    await user.click(screen.getByText('Disabled'))
    expect(handleClick).not.toHaveBeenCalled()
  })

  it('renders different sizes correctly', () => {
    const { container: smallContainer } = render(<Button size="sm">Small</Button>)
    const { container: largeContainer } = render(<Button size="lg">Large</Button>)

    const smallButton = smallContainer.querySelector('button')
    const largeButton = largeContainer.querySelector('button')

    expect(smallButton).toHaveClass('h-8')
    expect(largeButton).toHaveClass('h-10')
  })
})
