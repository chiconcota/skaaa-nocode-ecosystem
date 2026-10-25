import React, { useEffect, useRef, useCallback, useMemo } from 'react';
import CodeMirror from '@uiw/react-codemirror';
import { javascript } from '@codemirror/lang-javascript';
import { autocompletion } from '@codemirror/autocomplete';
import { __ } from '@wordpress/i18n';

export default function CodeMirrorEditor({ value, onChange }) {
    const completionsRef = useRef([]);

    useEffect(() => {
        const fetchSchema = window.wp?.apiFetch ? window.wp.apiFetch : null;
        if (!fetchSchema) {
            console.error("wp.apiFetch is not available. Schema Autocomplete is disabled.");
            return;
        }

        fetchSchema({ path: '/skaaa-logic/v1/schema' })
            .then(data => {
                if (!Array.isArray(data)) return;
                
                const hints = data.map(item => ({
                    label: item.value,      // e.g. [core.users.fullname]
                    type: "variable",
                    detail: item.key.split(' - ')[1] || 'Skaaa Logic Engine',
                    info: item.key         
                }));
                // Add default keywords so they get hinted
                hints.push(
                    { label: 'var ', type: 'keyword', info: __( 'Declare local variable', 'skaaa-logic-engine' ) },
                    { label: 'data', type: 'variable', info: __( 'Output static/dynamic data variable', 'skaaa-logic-engine' ) },
                    { label: 'visible', type: 'variable', info: __( 'Visibility control variable (true/false)', 'skaaa-logic-engine' ) },
                    { label: 'CONCAT', type: 'function', info: __( 'Concatenate multiple strings', 'skaaa-logic-engine' ), detail: __( 'String Function', 'skaaa-logic-engine' ) },
                    { label: 'IF', type: 'function', info: __( 'IF(condition, true_val, false_val)', 'skaaa-logic-engine' ), detail: __( 'Control Flow', 'skaaa-logic-engine' ) }
                );

                completionsRef.current = hints;
            })
            .catch(err => console.error("SkaaaFX Schema Fetch Error:", err));
    }, []);

    const skaaaAutocomplete = useCallback((context) => {
        // Mẫu Regex bắt cả chữ thường và ký tự dấu ngoặc vuông:
        let word = context.matchBefore(/\[[-\w.]*$/); 
        
        // Nếu không dính dấu ngoặc [], thử lấy chữ thường (như gõ chữ 'va' ra 'var')
        if (!word) {
            word = context.matchBefore(/\w*$/);
        }

        if (!word) return null;
        
        // Đừng hiện dropdown cản trở tầm nhìn nếu chưa gõ gì (trừ khi cố tình ấn Ctrl+Space)
        if (word.from === word.to && !context.explicit) return null;

        return {
            from: word.from,
            options: completionsRef.current,
            validFor: /^\[?[-\w.]*$/ 
        };
    }, []);

    const extensionsArray = useMemo(() => [
        javascript({ jsx: false }),
        autocompletion({ override: [skaaaAutocomplete] })
    ], [skaaaAutocomplete]);

    return (
        <div style={{ border: '1px solid #374151', borderRadius: '8px', overflow: 'hidden' }}>
            <CodeMirror
                value={value}
                height="350px"
                theme="dark" // Nền đen ngầu
                extensions={extensionsArray}
                onChange={(val) => {
                    onChange(val);
                }}
            />
        </div>
    );
}
