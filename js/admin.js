/**
 * P2P to Taxonomy Migration Admin Interface
 * 
 * This file contains the JavaScript for the migration admin interface,
 * allowing users to configure and execute the P2P to Taxonomy migration.
 */

(function() {
    'use strict';

    const MigrationInterface = {
        /**
         * Initialize the migration interface
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadConfig();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.$container = document.getElementById('p2p-migration-container');
            this.$form = document.getElementById('p2p-migration-form');
            this.$progressBar = document.getElementById('migration-progress');
            this.$progressText = document.getElementById('progress-text');
            this.$logContainer = document.getElementById('migration-log');
            this.$startBtn = document.getElementById('start-migration-btn');
            this.$pauseBtn = document.getElementById('pause-migration-btn');
            this.$resumeBtn = document.getElementById('resume-migration-btn');
            this.$cancelBtn = document.getElementById('cancel-migration-btn');
            this.$settingsForm = document.getElementById('settings-form');
            this.$dryRunCheckbox = document.getElementById('dry-run-mode');
            this.$batchSizeInput = document.getElementById('batch-size');
            this.$mappingTable = document.getElementById('mapping-table');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            if (this.$startBtn) {
                this.$startBtn.addEventListener('click', this.startMigration.bind(this));
            }
            if (this.$pauseBtn) {
                this.$pauseBtn.addEventListener('click', this.pauseMigration.bind(this));
            }
            if (this.$resumeBtn) {
                this.$resumeBtn.addEventListener('click', this.resumeMigration.bind(this));
            }
            if (this.$cancelBtn) {
                this.$cancelBtn.addEventListener('click', this.cancelMigration.bind(this));
            }
            if (this.$settingsForm) {
                this.$settingsForm.addEventListener('submit', this.saveSettings.bind(this));
            }
        },

        /**
         * Load configuration from localStorage or server
         */
        loadConfig: function() {
            const self = this;
            const config = localStorage.getItem('p2p_migration_config');
            
            if (config) {
                const parsed = JSON.parse(config);
                if (this.$batchSizeInput) {
                    this.$batchSizeInput.value = parsed.batchSize || 50;
                }
                if (this.$dryRunCheckbox) {
                    this.$dryRunCheckbox.checked = parsed.dryRun || false;
                }
            }

            // Load initial mapping data
            this.loadMappingData();
        },

        /**
         * Load P2P to Taxonomy mapping data
         */
        loadMappingData: function() {
            const self = this;
            const nonce = document.querySelector('[data-nonce]')?.dataset.nonce;

            if (!nonce) {
                console.warn('Migration nonce not found');
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'p2p_migration_get_mappings',
                    nonce: nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    self.displayMappingTable(data.data);
                } else {
                    self.addLog('Error loading mappings: ' + data.data.message, 'error');
                }
            })
            .catch(error => {
                self.addLog('Error fetching mappings: ' + error.message, 'error');
                console.error('Mapping load error:', error);
            });
        },

        /**
         * Display the mapping table with P2P relationships and their taxonomy equivalents
         */
        displayMappingTable: function(mappings) {
            if (!this.$mappingTable) return;

            let html = '<table class="widefat striped">';
            html += '<thead><tr><th>Post Type</th><th>Relationship</th><th>Mapped To</th><th>Count</th></tr></thead>';
            html += '<tbody>';

            mappings.forEach(mapping => {
                html += `<tr>
                    <td>${this.escapeHtml(mapping.post_type)}</td>
                    <td>${this.escapeHtml(mapping.relationship)}</td>
                    <td>${this.escapeHtml(mapping.mapped_taxonomy || 'Not mapped')}</td>
                    <td>${mapping.count}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            this.$mappingTable.innerHTML = html;
        },

        /**
         * Start the migration process
         */
        startMigration: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to start the migration? This action may take a while.')) {
                return;
            }

            const settings = this.getSettings();
            this.addLog('Starting migration with settings: ' + JSON.stringify(settings), 'info');

            this.setMigrationState('running');
            this.migrateBatch(0, settings);
        },

        /**
         * Migrate a batch of items
         */
        migrateBatch: function(offset, settings) {
            const self = this;
            const nonce = document.querySelector('[data-nonce]')?.dataset.nonce;

            if (!nonce) {
                self.addLog('Error: Migration nonce not found', 'error');
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'p2p_migration_process_batch',
                    nonce: nonce,
                    offset: offset,
                    batch_size: settings.batchSize,
                    dry_run: settings.dryRun ? 1 : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    self.addLog(`Processed batch: ${data.data.processed} items, ${data.data.migrated} migrated`, 'success');
                    self.updateProgress(data.data.progress, data.data.total);

                    if (data.data.continue) {
                        // Continue with next batch
                        if (self.migrationPaused) {
                            self.addLog('Migration paused', 'info');
                            self.setMigrationState('paused');
                            return;
                        }
                        setTimeout(() => {
                            self.migrateBatch(offset + settings.batchSize, settings);
                        }, 500);
                    } else {
                        self.addLog('Migration completed successfully!', 'success');
                        self.setMigrationState('completed');
                    }
                } else {
                    self.addLog('Error: ' + data.data.message, 'error');
                    self.setMigrationState('error');
                }
            })
            .catch(error => {
                self.addLog('Error during migration: ' + error.message, 'error');
                self.setMigrationState('error');
                console.error('Migration error:', error);
            });
        },

        /**
         * Pause the migration
         */
        pauseMigration: function(e) {
            e.preventDefault();
            this.migrationPaused = true;
            this.addLog('Pausing migration...', 'info');
        },

        /**
         * Resume the migration
         */
        resumeMigration: function(e) {
            e.preventDefault();
            this.migrationPaused = false;
            const settings = this.getSettings();
            const currentOffset = this.getCurrentOffset();
            this.addLog('Resuming migration...', 'info');
            this.setMigrationState('running');
            this.migrateBatch(currentOffset, settings);
        },

        /**
         * Cancel the migration
         */
        cancelMigration: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to cancel the migration? This cannot be undone.')) {
                return;
            }

            this.migrationPaused = true;
            const nonce = document.querySelector('[data-nonce]')?.dataset.nonce;

            if (!nonce) {
                this.addLog('Error: Migration nonce not found', 'error');
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'p2p_migration_cancel',
                    nonce: nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.addLog('Migration cancelled', 'warning');
                    this.setMigrationState('idle');
                } else {
                    this.addLog('Error cancelling migration: ' + data.data.message, 'error');
                }
            })
            .catch(error => {
                this.addLog('Error during cancel: ' + error.message, 'error');
                console.error('Cancel error:', error);
            });
        },

        /**
         * Save migration settings
         */
        saveSettings: function(e) {
            e.preventDefault();

            const settings = this.getSettings();
            localStorage.setItem('p2p_migration_config', JSON.stringify(settings));
            
            this.addLog('Settings saved successfully', 'success');
        },

        /**
         * Get current migration settings
         */
        getSettings: function() {
            return {
                batchSize: parseInt(this.$batchSizeInput?.value || 50),
                dryRun: this.$dryRunCheckbox?.checked || false
            };
        },

        /**
         * Get the current migration offset
         */
        getCurrentOffset: function() {
            // This would be tracked server-side in a real implementation
            return 0;
        },

        /**
         * Update the progress bar
         */
        updateProgress: function(current, total) {
            if (!this.$progressBar) return;

            const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
            this.$progressBar.style.width = percentage + '%';
            this.$progressBar.textContent = percentage + '%';

            if (this.$progressText) {
                this.$progressText.textContent = `Progress: ${current} / ${total} items`;
            }
        },

        /**
         * Add a log entry
         */
        addLog: function(message, type = 'info') {
            if (!this.$logContainer) return;

            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${type}`;
            logEntry.textContent = `[${timestamp}] ${message}`;

            this.$logContainer.appendChild(logEntry);
            this.$logContainer.scrollTop = this.$logContainer.scrollHeight;
        },

        /**
         * Set the migration state
         */
        setMigrationState: function(state) {
            const states = ['idle', 'running', 'paused', 'completed', 'error'];
            
            states.forEach(s => {
                if (this.$container) {
                    this.$container.classList.remove(`state-${s}`);
                }
            });

            if (this.$container) {
                this.$container.classList.add(`state-${state}`);
            }

            // Update button states
            this.updateButtonStates(state);
        },

        /**
         * Update button visibility and state based on migration state
         */
        updateButtonStates: function(state) {
            const running = state === 'running';
            const paused = state === 'paused';
            const idle = state === 'idle' || state === 'completed';

            if (this.$startBtn) {
                this.$startBtn.disabled = !idle;
                this.$startBtn.style.display = idle ? 'inline-block' : 'none';
            }
            if (this.$pauseBtn) {
                this.$pauseBtn.disabled = !running;
                this.$pauseBtn.style.display = running ? 'inline-block' : 'none';
            }
            if (this.$resumeBtn) {
                this.$resumeBtn.disabled = !paused;
                this.$resumeBtn.style.display = paused ? 'inline-block' : 'none';
            }
            if (this.$cancelBtn) {
                this.$cancelBtn.disabled = idle;
                this.$cancelBtn.style.display = !idle ? 'inline-block' : 'none';
            }
        },

        /**
         * Escape HTML characters
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            MigrationInterface.init();
        });
    } else {
        MigrationInterface.init();
    }

    // Expose globally if needed
    window.P2PMigration = MigrationInterface;
})();
