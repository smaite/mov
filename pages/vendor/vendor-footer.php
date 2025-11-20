            </div>
        </div>
    </div>

    <script>
    // Toggle sidebar function
    function toggleVendorSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('hidden');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        const toggleButton = event.target.closest('[onclick="toggleVendorSidebar()"]');
        
        if (!toggleButton && !sidebar.contains(event.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        }
    });

    // Initialize sidebar state based on screen size
    function initializeSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        if (window.innerWidth <= 1024) {
            // Mobile: hide sidebar by default
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        } else {
            // Desktop: always show sidebar
            sidebar.classList.add('open');
            overlay.classList.add('hidden');
        }
    }

    // Handle window resize
    window.addEventListener('resize', initializeSidebar);
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializeSidebar);

    // Auto-refresh for verification pending page
    <?php if (basename($_SERVER['PHP_SELF']) === 'verification-pending.php' && (!isset($_SESSION['status']) || $_SESSION['status'] === 'pending')): ?>
    setTimeout(function() {
        window.location.reload();
    }, 30000); // Refresh every 30 seconds
    <?php endif; ?>

    // Common utility functions
    function showLoadingSpinner(button) {
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        button.disabled = true;
        return originalContent;
    }

    function hideLoadingSpinner(button, originalContent) {
        button.innerHTML = originalContent;
        button.disabled = false;
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Confirm dialog helper
    function confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }

    // Format currency helper
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-NP', {
            style: 'currency',
            currency: 'NPR',
            minimumFractionDigits: 2
        }).format(amount);
    }

    // Date formatting helper
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-NP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Time ago helper
    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval > 1) return interval + " years ago";
        
        interval = Math.floor(seconds / 2592000);
        if (interval > 1) return interval + " months ago";
        
        interval = Math.floor(seconds / 86400);
        if (interval > 1) return interval + " days ago";
        
        interval = Math.floor(seconds / 3600);
        if (interval > 1) return interval + " hours ago";
        
        interval = Math.floor(seconds / 60);
        if (interval > 1) return interval + " minutes ago";
        
        return "Just now";
    }

    // Image preview helper for product uploads
    function previewImages(input, previewContainer) {
        previewContainer.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            previewContainer.classList.remove('hidden');
            
            Array.from(input.files).forEach((file, index) => {
                if (index < 5) { // Max 5 images
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border">
                            <div class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cursor-pointer hover:bg-red-600" onclick="removeImagePreview(this)">×</div>
                        `;
                        previewContainer.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            });
        } else {
            previewContainer.classList.add('hidden');
        }
    }

    // Remove image preview
    function removeImagePreview(element) {
        element.parentElement.remove();
    }

    // Bulk actions helper
    function toggleBulkActions(checkboxContainer, actionsContainer) {
        const checkboxes = checkboxContainer.querySelectorAll('input[type="checkbox"]:checked');
        const hasChecked = checkboxes.length > 0;
        
        if (hasChecked) {
            actionsContainer.classList.remove('hidden');
            actionsContainer.querySelector('.selected-count').textContent = checkboxes.length;
        } else {
            actionsContainer.classList.add('hidden');
        }
    }

    // Select all checkboxes
    function toggleSelectAll(masterCheckbox, itemCheckboxes) {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = masterCheckbox.checked;
        });
    }

    // Search/filter helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Export data helper
    function exportData(data, filename, type = 'csv') {
        let content;
        let mimeType;
        
        if (type === 'csv') {
            content = convertToCSV(data);
            mimeType = 'text/csv';
        } else if (type === 'json') {
            content = JSON.stringify(data, null, 2);
            mimeType = 'application/json';
        }
        
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Convert array to CSV
    function convertToCSV(data) {
        if (!data || data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const csvHeaders = headers.join(',');
        
        const csvRows = data.map(row => {
            return headers.map(header => {
                const value = row[header];
                return typeof value === 'string' && value.includes(',') 
                    ? `"${value.replace(/"/g, '""')}"` 
                    : value;
            }).join(',');
        });
        
        return [csvHeaders, ...csvRows].join('\n');
    }

    // Print helper
    function printElement(elementId) {
        const element = document.getElementById(elementId);
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .no-print { display: none; }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    }

    // Chart helper for analytics
    function createChart(canvasId, type, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        
        const ctx = canvas.getContext('2d');
        
        // Default options
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        };
        
        // Merge with provided options
        const chartOptions = { ...defaultOptions, ...options };
        
        // This would integrate with Chart.js or similar library
        // For now, return a mock object
        return {
            canvas,
            ctx,
            update: () => {},
            destroy: () => {}
        };
    }

    // Initialize tooltips
    function initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-800 rounded shadow-lg';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.top = (e.pageY - 30) + 'px';
                tooltip.style.left = (e.pageX + 10) + 'px';
                tooltip.id = 'tooltip';
                
                document.body.appendChild(tooltip);
            });
            
            element.addEventListener('mouseleave', function() {
                const tooltip = document.getElementById('tooltip');
                if (tooltip) {
                    document.body.removeChild(tooltip);
                }
            });
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeTooltips();
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });

    // Console log for debugging
    console.log('Vendor system initialized successfully');
    </script>
</body>
</html>