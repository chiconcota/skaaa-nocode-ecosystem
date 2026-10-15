<?php
/**
 * View template: Giao diện Trang Quản trị Skaaa Bridge & Deployment
 *
 * @package Skaaai
 * @version 1.5.5
 */

namespace Skaaai;

defined( 'ABSPATH' ) || exit;

/**
 * @var string $role
 * @var string $remote_url
 * @var string $remote_token
 * @var bool   $allow_code_deploy
 * @var string $pairing_uri
 * @var string $secret_token
 * @var array  $custom_files
 */
?>
<div class="wrap skaaai-admin-wrap">
    <header class="skaaai-header">
        <div class="skaaai-header-left">
            <span class="skaaai-badge">v<?php echo esc_html( SKAAAI_VERSION ); ?></span>
            <h1><?php esc_html_e( 'Skaaa Bridge & Deployment', 'skaaai' ); ?></h1>
            <p class="skaaai-subtitle"><?php esc_html_e( '1-Click Sync & Deployment Bridge between Localhost Dev and Live Webhost.', 'skaaai' ); ?></p>
        </div>
        <div class="skaaai-header-right">
            <span class="skaaai-status-pill <?php echo $role === 'receiver' ? 'pill-green' : 'pill-blue'; ?>">
                ● <?php echo $role === 'receiver' ? esc_html__( 'Mode: Receiver (Webhost)', 'skaaai' ) : esc_html__( 'Mode: Sender (Localhost)', 'skaaai' ); ?>
            </span>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="skaaai-tabs">
        <button type="button" class="skaaai-tab-btn active" data-tab="pairing">
            <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Pairing & Connection', 'skaaai' ); ?>
        </button>
        <button type="button" class="skaaai-tab-btn" data-tab="deployer">
            <span class="dashicons dashicons-media-code"></span> <?php esc_html_e( 'Code & Node Deployer', 'skaaai' ); ?>
        </button>
        <?php if ( $role === 'sender' ) : ?>
        <button type="button" class="skaaai-tab-btn" data-tab="agent-cockpit">
            <span class="dashicons dashicons-superhero"></span> <?php esc_html_e( 'Agent Cockpit', 'skaaai' ); ?>
        </button>
        <?php endif; ?>
    </nav>

    <!-- TAB 1: PAIRING & CONNECTION -->
    <section id="tab-pairing" class="skaaai-tab-pane active">
        <form id="skaaai-pairing-form">
            <div class="skaaai-card">
                <h3><?php esc_html_e( 'Site Role Configuration', 'skaaai' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Choose whether this WordPress installation acts as a development sender or production receiver.', 'skaaai' ); ?></p>
                
                <div class="skaaai-radio-group">
                    <label class="skaaai-radio-card <?php echo $role === 'receiver' ? 'selected' : ''; ?>">
                        <input type="radio" name="role" value="receiver" <?php checked( $role, 'receiver' ); ?>>
                        <div class="radio-card-content">
                            <strong><?php esc_html_e( 'Receiver (Live Webhost / Production)', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'This site receives published pages, media, and code files from your PC.', 'skaaai' ); ?></span>
                        </div>
                    </label>

                    <label class="skaaai-radio-card <?php echo $role === 'sender' ? 'selected' : ''; ?>">
                        <input type="radio" name="role" value="sender" <?php checked( $role, 'sender' ); ?>>
                        <div class="radio-card-content">
                            <strong><?php esc_html_e( 'Sender (Localhost Dev / PC)', 'skaaai' ); ?></strong>
                            <span><?php esc_html_e( 'You design layout, write code, and push updates directly to the Live site.', 'skaaai' ); ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- RECEIVER VIEW -->
            <div id="section-receiver" class="skaaai-role-section <?php echo $role === 'receiver' ? '' : 'hidden'; ?>">
                <div class="skaaai-card">
                    <h3><?php esc_html_e( 'Receiver Pairing Information', 'skaaai' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Copy the Pairing Key below and paste it into your Localhost WordPress site.', 'skaaai' ); ?></p>

                    <div class="skaaai-form-group">
                        <label><?php esc_html_e( 'Universal Pairing Key', 'skaaai' ); ?></label>
                        <div class="skaaai-input-group">
                            <input type="text" id="skaaai-pairing-key-input" class="large-text code" readonly value="<?php echo esc_attr( $pairing_uri ); ?>">
                            <button type="button" class="button button-primary" id="btn-copy-pairing-key">
                                <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy Key', 'skaaai' ); ?>
                            </button>
                        </div>
                        <span class="help-text"><?php esc_html_e( 'Includes remote URL and encrypted secret token in one portable string.', 'skaaai' ); ?></span>
                    </div>

                    <div class="skaaai-form-group">
                        <label><?php esc_html_e( 'Secret Token', 'skaaai' ); ?></label>
                        <div class="skaaai-input-group">
                            <input type="password" id="skaaai-secret-token-display" class="regular-text code" readonly value="<?php echo esc_attr( $secret_token ); ?>">
                            <button type="button" class="button" id="btn-regenerate-token">
                                <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Regenerate Token', 'skaaai' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SENDER VIEW -->
            <div id="section-sender" class="skaaai-role-section <?php echo $role === 'sender' ? '' : 'hidden'; ?>">
                <div class="skaaai-card">
                    <h3><?php esc_html_e( 'Connect to Remote Live Webhost', 'skaaai' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Paste the Pairing Key generated by your online WordPress website.', 'skaaai' ); ?></p>

                    <div class="skaaai-form-group">
                        <label><?php esc_html_e( 'Paste Pairing Key (skaaai_pair://...)', 'skaaai' ); ?></label>
                        <textarea id="skaaai-paste-key-input" class="large-text code" rows="3" placeholder="skaaai_pair://ey..."></textarea>
                        <button type="button" class="button button-secondary" id="btn-parse-key" style="margin-top: 8px;">
                            <?php esc_html_e( 'Auto-fill Connection Details', 'skaaai' ); ?>
                        </button>
                    </div>

                    <div class="skaaai-grid-2">
                        <div class="skaaai-form-group">
                            <label><?php esc_html_e( 'Remote Webhost URL', 'skaaai' ); ?></label>
                            <input type="url" name="remote_url" id="remote_url" class="large-text" value="<?php echo esc_attr( $remote_url ); ?>" placeholder="https://example.com">
                        </div>
                        <div class="skaaai-form-group">
                            <label><?php esc_html_e( 'Remote Secret Token', 'skaaai' ); ?></label>
                            <input type="password" name="remote_token" id="remote_token" class="large-text code" value="<?php echo esc_attr( $remote_token ); ?>">
                        </div>
                    </div>

                    <div class="skaaai-actions-bar">
                        <button type="button" class="button button-secondary" id="btn-test-handshake">
                            <span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Test Connection (Handshake)', 'skaaai' ); ?>
                        </button>
                        <span id="handshake-result-status" class="skaaai-inline-result"></span>
                    </div>
                </div>

                <?php Ecosystem_Sync_UI::render_sender_card(); ?>
            </div>

            <div class="skaaai-submit-row">
                <button type="submit" class="button button-primary button-hero" id="btn-save-settings">
                    <?php esc_html_e( 'Save Configuration', 'skaaai' ); ?>
                </button>
                <span id="save-settings-status" class="skaaai-inline-result"></span>
            </div>
        </form>
    </section>

    <!-- TAB 2: CODE & NODE DEPLOYER -->
    <section id="tab-deployer" class="skaaai-tab-pane">
        <div class="skaaai-card">
            <h3><?php esc_html_e( 'Security & Deployment Options', 'skaaai' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Control whether this server accepts incoming custom PHP node files.', 'skaaai' ); ?></p>
            
            <label class="skaaai-checkbox-label">
                <input type="checkbox" name="allow_code_deploy" id="allow_code_deploy" value="1" <?php checked( $allow_code_deploy ); ?>>
                <strong><?php esc_html_e( 'Allow Remote Code & Pluggable Node Deployment', 'skaaai' ); ?></strong>
                <span id="allow-code-deploy-status" class="skaaai-inline-result" style="margin-left: 10px;"></span>
            </label>
            <p class="description" style="margin-left: 24px;">
                <?php esc_html_e( 'When enabled, paired senders can deploy PHP classes into the sandbox directory. Strict syntax validation and backup points are enforced automatically.', 'skaaai' ); ?>
            </p>
        </div>

        <div class="skaaai-grid-2">
            <!-- DEPLOY TOOL (FOR SENDER) -->
            <div class="skaaai-card">
                <h3><?php esc_html_e( '1-Click Code Deployer (Push to Remote)', 'skaaai' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Deploy custom node or helper PHP code directly to the paired remote host.', 'skaaai' ); ?></p>

                <div class="skaaai-form-group">
                    <label><?php esc_html_e( 'File Name (*.php)', 'skaaai' ); ?></label>
                    <input type="text" id="deploy_filename" class="large-text code" placeholder="class-my-custom-node.php">
                </div>

                <div class="skaaai-form-group">
                    <label><?php esc_html_e( 'PHP Source Code', 'skaaai' ); ?></label>
                    <textarea id="deploy_code" class="large-text code" rows="12" placeholder="<?php echo esc_attr( "<?php\ndefined( 'ABSPATH' ) || exit;\n\n// Custom Node Logic..." ); ?>"></textarea>
                </div>

                <label class="skaaai-checkbox-label">
                    <input type="checkbox" id="deploy_overwrite" value="1" checked>
                    <?php esc_html_e( 'Overwrite if file exists (creates .bak backup)', 'skaaai' ); ?>
                </label>

                <div style="margin-top: 15px;">
                    <button type="button" class="button button-primary" id="btn-deploy-code">
                        <span class="dashicons dashicons-upload"></span> <?php esc_html_e( '🚀 Deploy to Remote Webhost', 'skaaai' ); ?>
                    </button>
                    <div id="deploy-output-status" class="skaaai-console-box hidden"></div>
                </div>
            </div>

            <!-- DEPLOYED FILES LIST (ON RECEIVER & SENDER) -->
            <div class="skaaai-card">
                <h3><?php esc_html_e( 'Active Custom Nodes on this Server', 'skaaai' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Files currently loaded inside wp-content/skaaa-custom-nodes/ (Persistent Storage)', 'skaaai' ); ?></p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Filename', 'skaaai' ); ?></th>
                            <th><?php esc_html_e( 'Size', 'skaaai' ); ?></th>
                            <th><?php esc_html_e( 'Modified', 'skaaai' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'skaaai' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="custom-nodes-tbody">
                        <?php if ( empty( $custom_files ) ) : ?>
                            <tr id="no-custom-files-row">
                                <td colspan="4" class="text-center" style="color: #666; font-style: italic;">
                                    <?php esc_html_e( 'No custom node files deployed yet.', 'skaaai' ); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $custom_files as $cf ) : ?>
                                <tr data-file="<?php echo esc_attr( $cf['filename'] ); ?>">
                                    <td>
                                        <strong><code><?php echo esc_html( $cf['filename'] ); ?></code></strong>
                                        <?php if ( ! empty( $cf['has_bak'] ) ) : ?>
                                            <span class="skaaai-bak-badge" title="<?php esc_attr_e( 'Backup file exists (.bak)', 'skaaai' ); ?>">📦 .bak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="file-size"><?php echo esc_html( size_format( $cf['size'] ) ); ?></td>
                                    <td class="file-modified"><?php echo esc_html( date_i18n( 'Y-m-d H:i', $cf['modified'] ) ); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button type="button" class="button button-small btn-load-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Load into code editor', 'skaaai' ); ?>">
                                            <span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Edit', 'skaaai' ); ?>
                                        </button>
                                        <?php if ( $role === 'sender' ) : ?>
                                            <button type="button" class="button button-small btn-push-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Push to paired remote webhost', 'skaaai' ); ?>">
                                                <span class="dashicons dashicons-upload" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Push', 'skaaai' ); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete btn-delete-file" data-file="<?php echo esc_attr( $cf['filename'] ); ?>" title="<?php esc_attr_e( 'Delete locally and remove from Live Webhost', 'skaaai' ); ?>">
                                                <?php esc_html_e( 'Delete', 'skaaai' ); ?>
                                            </button>
                                        <?php else : ?>
                                            <span class="skaaai-readonly-badge" title="<?php esc_attr_e( 'Managed by Local Dev. Direct deletion is disabled on Live.', 'skaaai' ); ?>">
                                                <span class="dashicons dashicons-lock" style="font-size:13px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Live Protected', 'skaaai' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php if ( $role === 'sender' ) : ?>
        <?php Harness_Initializer::render_admin_tab(); ?>
    <?php endif; ?>
</div>

<script type="text/html" id="tmpl-skaaai-custom-node-row">
    <tr data-file="{{ data.filename }}">
        <td><strong><code>{{ data.filename }}</code></strong><# if ( data.has_bak ) { #><span class="skaaai-bak-badge" title="<?php esc_attr_e( 'Backup file exists (.bak)', 'skaaai' ); ?>">📦 .bak</span><# } #></td>
        <td class="file-size">{{ data.size }}</td>
        <td class="file-modified">{{ data.modified }}</td>
        <td style="white-space: nowrap;">
            <button type="button" class="button button-small btn-load-file" data-file="{{ data.filename }}" title="<?php esc_attr_e( 'Load into code editor', 'skaaai' ); ?>"><span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Edit', 'skaaai' ); ?></button>
            <# if ( data.is_sender ) { #>
                <button type="button" class="button button-small btn-push-file" data-file="{{ data.filename }}" title="<?php esc_attr_e( 'Push to paired remote webhost', 'skaaai' ); ?>"><span class="dashicons dashicons-upload" style="font-size:14px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Push', 'skaaai' ); ?></button>
                <button type="button" class="button button-small button-link-delete btn-delete-file" data-file="{{ data.filename }}"><?php esc_html_e( 'Delete', 'skaaai' ); ?></button>
            <# } else { #>
                <span class="skaaai-readonly-badge"><span class="dashicons dashicons-lock" style="font-size:13px;vertical-align:middle;margin-top:-2px;"></span> <?php esc_html_e( 'Live Protected', 'skaaai' ); ?></span>
            <# } #>
        </td>
    </tr>
</script>
