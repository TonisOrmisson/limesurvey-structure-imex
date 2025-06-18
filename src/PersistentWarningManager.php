<?php

namespace tonisormisson\ls\structureimex;

use PluginBase;

/**
 * Manages persistent warnings that remain visible until explicitly dismissed by the user
 */
class PersistentWarningManager
{
    private const SESSION_KEY = 'structureimex_persistent_warnings';
    
    private StructureImEx $plugin;
    
    public function __construct(StructureImEx $plugin)
    {
        $this->plugin = $plugin;
    }
    
    /**
     * Add a warning that will be displayed as a dismissible flash message
     */
    public function addWarning(string $message, string $type = 'warning', ?string $id = null): void
    {
        $warnings = $this->getWarnings();
        
        $warningId = $id ?? $this->generateWarningId($message);
        
        $warnings[$warningId] = [
            'id' => $warningId,
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        
        $this->saveWarnings($warnings);
    }
    
    /**
     * Dismiss a specific warning (remove it completely)
     */
    public function dismissWarning(string $warningId): void
    {
        $warnings = $this->getWarnings();
        
        if (isset($warnings[$warningId])) {
            unset($warnings[$warningId]);
            $this->saveWarnings($warnings);
        }
    }
    
    /**
     * Clear all warnings
     */
    public function clearAllWarnings(): void
    {
        $this->saveWarnings([]);
    }
    
    /**
     * Get all warnings
     */
    public function getActiveWarnings(): array
    {
        return $this->getWarnings();
    }
    
    /**
     * Check if there are any warnings
     */
    public function hasActiveWarnings(): bool
    {
        return !empty($this->getWarnings());
    }
    
    /**
     * Get warnings from session
     */
    private function getWarnings(): array
    {
        $session = $this->plugin->getSession();
        
        return $session->get(self::SESSION_KEY, []);
    }
    
    /**
     * Save warnings to session
     */
    private function saveWarnings(array $warnings): void
    {
        $session = $this->plugin->getSession();
        $session->add(self::SESSION_KEY, $warnings);
    }
    
    /**
     * Generate a unique warning ID
     */
    private function generateWarningId(string $message): string
    {
        return 'warning_' . md5($message . time());
    }
    
    /**
     * Add multiple warnings for validation errors
     */
    public function addValidationWarnings(array $validationErrors, string $context = ''): void
    {
        foreach ($validationErrors as $error) {
            $message = $context ? "$context: $error" : $error;
            $this->addWarning($message, 'warning');
        }
    }
    
    /**
     * Render warnings as HTML and clear them (flash message behavior)
     */
    public function renderWarnings(): string
    {
        $warnings = $this->getActiveWarnings();
        
        if (empty($warnings)) {
            return '';
        }
        
        $html = '';
        foreach ($warnings as $warning) {
            $alertClass = $this->getAlertClass($warning['type']);
            $html .= $this->renderSingleWarning($warning, $alertClass);
        }
        
        // Clear warnings after rendering (flash message behavior)
        $this->clearAllWarnings();
        
        return $html;
    }
    
    /**
     * Render a single warning
     */
    private function renderSingleWarning(array $warning, string $alertClass): string
    {
        $warningId = htmlspecialchars($warning['id']);
        $message = htmlspecialchars($warning['message']);
        
        return "
        <div class='alert $alertClass alert-dismissible fade show flash-warning' data-warning-id='$warningId' role='alert'>
            <div class='d-flex justify-content-between align-items-start'>
                <div class='alert-message'>
                    <strong>Import Warning:</strong> $message
                </div>
                <div class='alert-actions'>
                    <button type='button' class='btn-close btn-sm' data-dismiss='alert' aria-label='Close' onclick='dismissFlashWarning(this)'></button>
                </div>
            </div>
        </div>";
    }
    
    /**
     * Get Bootstrap alert class for warning type
     */
    private function getAlertClass(string $type): string
    {
        $classes = [
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            'success' => 'alert-success'
        ];
        
        return $classes[$type] ?? 'alert-warning';
    }
    
    /**
     * Get JavaScript for handling dismissals
     */
    public function getJavaScript(): string
    {
        return "
        <script>
        function dismissFlashWarning(button) {
            // Find the alert element and hide it with animation
            const alertElement = button.closest('.alert');
            if (alertElement) {
                // Add fade out animation
                alertElement.classList.add('fade-out');
                
                // Remove the element after animation completes
                setTimeout(function() {
                    alertElement.remove();
                }, 300);
            }
        }
        
        function dismissAllWarnings() {
            const warnings = document.querySelectorAll('.flash-warning');
            warnings.forEach(function(warning) {
                const button = warning.querySelector('.btn-close');
                if (button) {
                    dismissFlashWarning(button);
                }
            });
        }
        </script>
        
        <style>
        .flash-warning {
            margin-bottom: 10px;
            border-left: 4px solid #ffc107;
            transition: opacity 0.3s ease-out;
        }
        .flash-warning.fade-out {
            opacity: 0;
            transform: translateX(100%);
        }
        .flash-warning .alert-message {
            flex: 1;
            word-wrap: break-word;
        }
        .flash-warning .alert-actions {
            margin-left: 15px;
        }
        .flash-warning .btn-close {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        </style>";
    }
}
