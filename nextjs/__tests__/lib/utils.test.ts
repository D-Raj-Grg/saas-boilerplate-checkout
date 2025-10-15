import { cn } from '@/lib/utils'

describe('Utils', () => {
  describe('cn (className merger)', () => {
    it('should merge class names correctly', () => {
      const result = cn('text-red-500', 'bg-blue-500')
      expect(result).toBe('text-red-500 bg-blue-500')
    })

    it('should handle conditional classes', () => {
      const result = cn('base-class', false && 'conditional-class', 'another-class')
      expect(result).toBe('base-class another-class')
    })

    it('should override conflicting Tailwind classes', () => {
      const result = cn('text-red-500', 'text-blue-500')
      expect(result).toBe('text-blue-500')
    })

    it('should handle empty inputs', () => {
      const result = cn()
      expect(result).toBe('')
    })

    it('should handle undefined and null', () => {
      const result = cn(undefined, null, 'valid-class')
      expect(result).toBe('valid-class')
    })
  })
})
