/**
 * Common error handling utilities for file processing operations
 */

export interface FileWithUrl {
  filename?: string
  name?: string
  download_url?: string
  localUrl?: string
}

export interface ErrorResponse {
  success: false
  filename: string
  download_url: string
  message: string
}

/**
 * Creates a standardized error response for failed file operations
 */
export const createErrorResponse = (file: FileWithUrl, error: any): ErrorResponse => ({
  success: false,
  filename: file.filename || file.name || 'unknown',
  download_url: file.download_url || file.localUrl || 'unknown',
  message: error.message || 'Processing failed'
})

/**
 * Extracts error message from API response with fallback
 */
export const getApiErrorMessage = (response: any, fallback: string = 'Request failed'): string => {
  return response.error?.message ||
         response.message ||
         response.result?.message ||
         fallback
}

/**
 * Creates a transfer failed error response with descriptive message
 */
export const createTransferErrorResponse = (file: FileWithUrl, errorMessage: string): ErrorResponse => ({
  success: false,
  filename: file.filename || file.name || 'unknown',
  download_url: file.download_url || file.localUrl || 'unknown',
  message: `Transfer failed: ${errorMessage}`
})
