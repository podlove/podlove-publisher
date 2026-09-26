import { __, sprintf } from '../plugins/translations'
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
  status: 'failed'
  filename: string
  download_url: string
  message: string
}

/**
 * Creates a standardized error response for failed file operations
 */
export const createErrorResponse = (file: FileWithUrl, error: any): ErrorResponse => ({
  success: false,
  status: 'failed',
  filename: file.filename || file.name || 'unknown',
  download_url: file.download_url || file.localUrl || 'unknown',
  message: error.message || __('Processing failed', 'podlove-podcasting-plugin-for-wordpress')
})

/**
 * Extracts error message from API response with fallback
 */
export const getApiErrorMessage = (response: any, fallback: string = __('Request failed', 'podlove-podcasting-plugin-for-wordpress')): string => {
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
  status: 'failed',
  filename: file.filename || file.name || 'unknown',
  download_url: file.download_url || file.localUrl || 'unknown',
  message: sprintf(__('Transfer failed: %s', 'podlove-podcasting-plugin-for-wordpress'), errorMessage)
})
