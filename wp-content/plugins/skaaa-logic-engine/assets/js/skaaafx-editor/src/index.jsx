import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, Modal } from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import CodeMirrorEditor from './CodeMirrorEditor';

// 1. Add Attribute skaaaDynamicBinding
const addSkaaaDynamicBindingAttribute = (settings, name) => {
    if (name.startsWith('skaaaaa-builder/')) {
        settings.attributes = {
            ...settings.attributes,
            skaaaDynamicBinding: {
                type: 'object',
                // Hợp nhất duy nhất vào 1 biến script
                default: { script: '' }
            }
        };
    }
    return settings;
};

addFilter(
    'blocks.registerBlockType',
    'skaaa-logic-engine/add-dynamic-binding-attribute',
    addSkaaaDynamicBindingAttribute
);

// 2. The React Component for the Editor Modal
const SkaaaFXEditorModal = ({ isOpen, onRequestClose, scriptData, onChange }) => {
    const [localScript, setLocalScript] = useState(scriptData);

    return (
        isOpen && (
            <Modal 
                title={__( '🪄 SkaaaFX Universal Editor', 'skaaa-logic-engine' )} 
                onRequestClose={onRequestClose} 
                style={{ minWidth: '700px', padding: '0' }}
            >
                <div style={{ padding: '24px' }}>
                    <div style={{ marginBottom: '16px', fontSize: '13px', color: '#4b5563', lineHeight: '1.6' }}>
                        <p style={{ margin: '0 0 8px 0' }}>
                            {__( 'Type [ to show Data suggestions. You CAN use // comments.', 'skaaa-logic-engine' )}
                        </p>
                        <p style={{ margin: '0' }}>
                            {__( 'Assign value to ', 'skaaa-logic-engine' )}
                            <strong><code style={{color: '#2563eb'}}>data</code></strong>
                            {__( ' to inject into UI. Assign true/false to ', 'skaaa-logic-engine' )}
                            <strong><code style={{color: '#16a34a'}}>visible</code></strong>
                            {__( ' to determine rendering.', 'skaaa-logic-engine' )}
                        </p>
                    </div>

                    <CodeMirrorEditor 
                        value={localScript} 
                        onChange={(val) => setLocalScript(val)} 
                    />

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '24px' }}>
                        <Button isSecondary onClick={onRequestClose}>
                            {__( 'Cancel', 'skaaa-logic-engine' )}
                        </Button>
                        <Button isPrimary onClick={() => { 
                            onChange(localScript); 
                            onRequestClose(); 
                        }}>
                            {__( 'Apply Script', 'skaaa-logic-engine' )}
                        </Button>
                    </div>
                </div>
            </Modal>
        )
    );
};

// 3. Higher Order Component
const withUniversalDynamicBinding = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name, attributes, setAttributes, isSelected } = props;

        // Chỉ hiển thị trên các block của Skaaa Builder
        if (!name.startsWith('skaaaaa-builder/')) {
            return <BlockEdit {...props} />;
        }

        const { skaaaDynamicBinding } = attributes;
        const [isEditorOpen, setIsEditorOpen] = useState(false);

        // Data cũ tương thích ngược (hoặc data mặc định)
        const bindingData = skaaaDynamicBinding || { script: '' };

        return (
            <Fragment>
                {bindingData.script ? (
                    <div className="skaaa-dynamic-binding-wrapper" style={{ display: 'contents' }}>
                        <BlockEdit {...props} />
                        <div style={{
                            position: 'absolute',
                            top: '-12px',
                            right: '-12px',
                            backgroundColor: '#6366f1',
                            color: 'white',
                            fontSize: '11px',
                            padding: '3px 8px',
                            borderRadius: '12px',
                            fontWeight: '600',
                            zIndex: 20,
                            pointerEvents: 'none',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                            letterSpacing: '0.2px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '4px'
                        }}>
                            <span>🪄</span> {__( 'Dynamic Data', 'skaaa-logic-engine' )}
                        </div>
                    </div>
                ) : (
                    <BlockEdit {...props} />
                )}
                {isSelected && (
                    <InspectorControls>
                        <PanelBody title={__( '🪄 Universal Dynamic Binding', 'skaaa-logic-engine' )} initialOpen={false}>
                            <div>
                                <p style={{ fontSize: '13px', color: '#64748b', marginBottom: '16px' }}>
                                    {__( 'Unify content binding and block visibility decision with SkaaaFX Engine.', 'skaaa-logic-engine' )}
                                </p>

                                {bindingData.script && (
                                    <div style={{ background: '#f8fafc', padding: '12px', borderLeft: '3px solid #6366f1', marginBottom: '16px', fontSize: '11px', maxHeight: '150px', overflowY: 'auto' }}>
                                        <pre style={{ background: 'none', color: '#334155', margin: 0, whiteSpace: 'pre-wrap' }}>
                                            {bindingData.script}
                                        </pre>
                                    </div>
                                )}

                                <Button 
                                    isSecondary 
                                    style={{ width: '100%', justifyContent: 'center', borderColor: '#4f46e5', color: '#4f46e5' }}
                                    onClick={() => setIsEditorOpen(true)}
                                >
                                    {__( 'Open CodeMirror Engine', 'skaaa-logic-engine' )}
                                </Button>

                                <SkaaaFXEditorModal 
                                    isOpen={isEditorOpen}
                                    onRequestClose={() => setIsEditorOpen(false)}
                                    scriptData={bindingData.script}
                                    onChange={(newScript) => setAttributes({
                                        skaaaDynamicBinding: { ...bindingData, script: newScript }
                                    })}
                                />
                            </div>
                        </PanelBody>
                    </InspectorControls>
                )}
            </Fragment>
        );
    };
}, 'withUniversalDynamicBinding');

addFilter(
    'editor.BlockEdit',
    'skaaa-logic-engine/with-dynamic-binding',
    withUniversalDynamicBinding
);
