/**
 * Common status determination utilities for file processing operations
 */

export type ProcessingStatus = 'completed' | 'completed_with_errors' | 'failed'
export type MigrationStatus = 'finished' | 'error'

/**
 * Determines the final transfer status based on success/failure counts
 */
export const determineTransferStatus = (
  hasErrors: boolean,
  successCount: number
): ProcessingStatus => {
  if (!hasErrors) return 'completed'
  if (successCount > 0) return 'completed_with_errors'
  return 'failed'
}

/**
 * Determines the final migration status based on error state
 */
export const determineMigrationStatus = (hasErrors: boolean): MigrationStatus => {
  return hasErrors ? 'error' : 'finished'
}

/**
 * Checks if a processing result indicates success
 */
export const isSuccessResult = (result: any): boolean => {
  if (typeof result === 'boolean') return result
  if (result && typeof result === 'object') {
    return result.success !== false
  }
  return false
}

/**
 * Counts successful results from an array of processing results
 */
export const countSuccessfulResults = (results: any[]): number => {
  return results.filter(isSuccessResult).length
}
