/**
 * Example Test File
 *
 * This is a basic example test to verify Jest is working correctly.
 * You can delete this file once you have your own tests.
 */

describe('Example Test Suite', () => {
  it('should pass a basic assertion', () => {
    expect(true).toBe(true)
  })

  it('should perform arithmetic correctly', () => {
    expect(2 + 2).toBe(4)
  })

  it('should handle arrays', () => {
    const arr = [1, 2, 3]
    expect(arr).toHaveLength(3)
    expect(arr).toContain(2)
  })

  it('should handle objects', () => {
    const obj = { name: 'Test', value: 42 }
    expect(obj).toHaveProperty('name')
    expect(obj.value).toBe(42)
  })
})
