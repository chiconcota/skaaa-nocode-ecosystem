import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Button, Placeholder, ToggleControl } from '@wordpress/components';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { TailwindPanel } from '../components/TailwindPanel.js';
export default function Edit( { attributes, setAttributes } ) {
    const { 
        sourceType = 'flat_table',
        sourceTable = '', 
        postType = 'post',
        category = '',
        enablePagination = false,
        paginationTargetId = '',
        paginationRange = 2,
        includePrevNext = true,
        inheritQuery = false,
        limit = 10, 
        slots = [], 
        filters = [], 
        tailwindClasses = '' 
    } = attributes;
    const [organisms, setOrganisms] = useState([]);
    
    // Load organisms for dropdown from global cache
    useEffect(() => {
        const data = window.skaaaOrganismsCache || {};
        const options = Object.values(data).map(org => ({
            label: org.name || org.id,
            value: org.id
        }));
        setOrganisms([
            { label: __( 'Select Symbol...', 'skaaa-no-code-design' ), value: '' },
            ...options
        ]);
    }, []);

    const isCurrentTableSystem = Boolean(sourceTable && (sourceTable.includes('_sys_') || sourceTable.startsWith('sys_')));
    const [showSystemTables, setShowSystemTables] = useState(isCurrentTableSystem);

    const tableOptions = useMemo(() => {
        const defaultOpt = [{ label: __( '-- Select Data Table --', 'skaaa-no-code-design' ), value: '' }];
        const appOpts = [];
        const sysOpts = [];

        if (window.skaaaDataDictionary) {
            Object.keys(window.skaaaDataDictionary).forEach(key => {
                const table = window.skaaaDataDictionary[key];
                const val = key.replace(/^wp_/, '');
                const isSys = val.includes('_sys_') || val.startsWith('sys_') || Boolean(table.__table_info?.is_system);
                const rawLabelName = table.__table_info ? table.__table_info.name : val;
                
                if (isSys) {
                    sysOpts.push({ label: `⚙️ ${rawLabelName} (${val})`, value: val });
                } else {
                    appOpts.push({ label: `📦 ${rawLabelName} (${val})`, value: val });
                }
            });
        }

        let combined = [...defaultOpt, ...appOpts];
        if (showSystemTables) {
            combined = [...combined, ...sysOpts];
        }

        if (sourceTable && !combined.find(o => o.value === sourceTable)) {
            combined.push({ label: `[Custom] ${sourceTable}`, value: sourceTable });
        }
        return combined;
    }, [sourceTable, showSystemTables]);

    const blockProps = useBlockProps();

    const addSlot = () => {
        const newSlots = [...(slots || []), { organismId: '', condition: '' }];
        setAttributes({ slots: newSlots });
    };

    const removeSlot = (indexToRemove) => {
        const newSlots = slots.filter((_, index) => index !== indexToRemove);
        setAttributes({ slots: newSlots });
    };

    const updateSlot = (index, key, value) => {
        const newSlots = [...slots];
        // Ensure organismId is always a string to satisfy block.json strict schema validation
        const finalValue = key === 'organismId' ? String(value) : value;
        newSlots[index] = { ...newSlots[index], [key]: finalValue };
        setAttributes({ slots: newSlots });
    };

    const addFilter = () => {
        const newFilters = [...(filters || []), { column: '', operator: '=', value: '' }];
        setAttributes({ filters: newFilters });
    };

    const removeFilter = (indexToRemove) => {
        const newFilters = filters.filter((_, index) => index !== indexToRemove);
        setAttributes({ filters: newFilters });
    };

    const updateFilter = (index, key, value) => {
        const newFilters = [...filters];
        newFilters[index] = { ...newFilters[index], [key]: value };
        setAttributes({ filters: newFilters });
    };

    const hasValidConfig = (sourceType === 'pagination' || sourceType === 'wp_posts' || sourceTable) && slots && slots.length > 0 && slots.some(s => s.organismId);

    return (
        <div { ...blockProps }>
            <InspectorControls>
                <PanelBody title={__( 'Data configuration', 'skaaa-no-code-design' )} initialOpen={true}>
                    <SelectControl
                        label={__( 'Source Type', 'skaaa-no-code-design' )}
                        value={sourceType}
                        options={[
                            { label: __( '📦 Flat Table (Skaaa Data Pro)', 'skaaa-no-code-design' ), value: 'flat_table' },
                            { label: __( '📰 WordPress Posts (wp_posts)', 'skaaa-no-code-design' ), value: 'wp_posts' },
                            { label: __( '🔢 Pagination Loop (Pages)', 'skaaa-no-code-design' ), value: 'pagination' }
                        ]}
                        onChange={(val) => setAttributes({ sourceType: val })}
                    />

                    {sourceType === 'pagination' ? (
                        <>
                            <TextControl
                                label={__( 'Target Query Container ID', 'skaaa-no-code-design' )}
                                value={paginationTargetId}
                                placeholder="blog-posts-grid"
                                onChange={(val) => setAttributes({ paginationTargetId: (val || '').replace(/^#/, '').trim() })}
                                help={__( 'Target loop container ID to paginate (e.g. blog-posts-grid).', 'skaaa-no-code-design' )}
                            />
                            <TextControl
                                label={__( 'Page Range (Sliding Window)', 'skaaa-no-code-design' )}
                                type="number"
                                value={paginationRange}
                                onChange={(val) => setAttributes({ paginationRange: parseInt(val, 10) || 2 })}
                                help={__( 'Number of pages around the current page.', 'skaaa-no-code-design' )}
                                min={1}
                                max={10}
                            />
                            <ToggleControl
                                label={__( 'Include Previous / Next Buttons', 'skaaa-no-code-design' )}
                                help={__( 'Add Previous and Next items into the loop rows.', 'skaaa-no-code-design' )}
                                checked={includePrevNext !== false}
                                onChange={(val) => setAttributes({ includePrevNext: val })}
                            />
                        </>
                    ) : sourceType === 'wp_posts' ? (
                        <>
                            <SelectControl
                                label={__( 'Post Type', 'skaaa-no-code-design' )}
                                value={postType}
                                options={[
                                    { label: __( 'Posts (post)', 'skaaa-no-code-design' ), value: 'post' },
                                    { label: __( 'Pages (page)', 'skaaa-no-code-design' ), value: 'page' }
                                ]}
                                onChange={(val) => setAttributes({ postType: val })}
                            />
                            <TextControl
                                label={__( 'Category (Slug or ID)', 'skaaa-no-code-design' )}
                                value={category}
                                placeholder={__( 'e.g. news or 5 (optional)', 'skaaa-no-code-design' )}
                                onChange={(val) => setAttributes({ category: val })}
                                help={__( 'Filter posts by category slug or ID. Leave blank for all.', 'skaaa-no-code-design' )}
                            />
                            <ToggleControl
                                label={__( 'Inherit Query from Template', 'skaaa-no-code-design' )}
                                help={__( 'Automatically inherit archive or category context on taxonomy pages.', 'skaaa-no-code-design' )}
                                checked={inheritQuery}
                                onChange={(val) => setAttributes({ inheritQuery: val })}
                            />
                        </>
                    ) : (
                        window.skaaaDataDictionary ? (
                            <>
                                <SelectControl
                                    label={__( 'Source Table (Flat Table)', 'skaaa-no-code-design' )}
                                    value={sourceTable}
                                    options={tableOptions}
                                    onChange={(val) => setAttributes({ sourceTable: val })}
                                    help={__( 'Select the data table provided by Skaaa Data Pro', 'skaaa-no-code-design' )}
                                />
                                <ToggleControl
                                    label={__( 'Show System Tables (Internal)', 'skaaa-no-code-design' )}
                                    help={__( 'Enable to view internal engine tables (sys_apps, sys_workflows, etc.)', 'skaaa-no-code-design' )}
                                    checked={showSystemTables}
                                    onChange={(val) => setShowSystemTables(val)}
                                />
                            </>
                        ) : (
                            <TextControl
                                label={__( 'Source Table (Flat Table)', 'skaaa-no-code-design' )}
                                value={sourceTable}
                                onChange={(val) => setAttributes({ sourceTable: val })}
                                help={__( 'For example: skaaa_data_doctors', 'skaaa-no-code-design' )}
                            />
                        )
                    )}

                    {sourceType !== 'pagination' && (
                        <TextControl
                            label={sourceType === 'wp_posts' ? __( 'Posts per Page (Limit)', 'skaaa-no-code-design' ) : __( 'Limit', 'skaaa-no-code-design' )}
                            type="number"
                            value={limit}
                            onChange={(val) => setAttributes({ limit: parseInt(val, 10) || 10 })}
                            min={1}
                            max={100}
                        />
                    )}
                </PanelBody>

                {sourceType === 'flat_table' && (
                    <PanelBody title={__( 'Filters', 'skaaa-no-code-design' )} initialOpen={false}>
                        {filters && filters.map((filter, index) => (
                            <div key={index} style={{ marginBottom: '16px', padding: '12px', border: '1px solid #e2e8f0', borderRadius: '6px', backgroundColor: '#f8fafc' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                                    <strong style={{ fontSize: '13px' }}>Filter #{index + 1}</strong>
                                    <Button 
                                        isDestructive 
                                        isSmall 
                                        icon="trash" 
                                        onClick={() => removeFilter(index)}
                                        label={__( 'Delete Filter', 'skaaa-no-code-design' )}
                                    />
                                </div>
                                
                                <TextControl
                                    label={__( 'Data field (Column)', 'skaaa-no-code-design' )}
                                    value={filter.column}
                                    onChange={(val) => updateFilter(index, 'column', val)}
                                    help={__( 'e.g. teacher_id', 'skaaa-no-code-design' )}
                                />

                                <SelectControl
                                    label={__( 'Operator', 'skaaa-no-code-design' )}
                                    value={filter.operator}
                                    options={[
                                        { label: '=', value: '=' },
                                        { label: '!=', value: '!=' },
                                        { label: '>', value: '>' },
                                        { label: '>=', value: '>=' },
                                        { label: '<', value: '<' },
                                        { label: '<=', value: '<=' },
                                        { label: 'LIKE', value: 'LIKE' },
                                        { label: 'IN', value: 'IN' },
                                        { label: 'JSON_CONTAINS', value: 'JSON_CONTAINS' }
                                    ]}
                                    onChange={(val) => updateFilter(index, 'operator', val)}
                                />
                                
                                <TextControl
                                    label={__( 'Value', 'skaaa-no-code-design' )}
                                    value={filter.value}
                                    onChange={(val) => updateFilter(index, 'value', val)}
                                    help={__( 'For example: {url:id} or static value', 'skaaa-no-code-design' )}
                                />
                            </div>
                        ))}
                        
                        <Button 
                            variant="secondary" 
                            onClick={addFilter}
                            style={{ width: '100%', justifyContent: 'center', marginTop: '8px' }}
                        >
                            {__( '+ Add Filter', 'skaaa-no-code-design' )}
                        </Button>
                    </PanelBody>
                )}

                <PanelBody title={__( 'Display conditions (Slots)', 'skaaa-no-code-design' )} initialOpen={true}>
                    {slots && slots.map((slot, index) => (
                        <div key={index} style={{ marginBottom: '16px', padding: '12px', border: '1px solid #e2e8f0', borderRadius: '6px', backgroundColor: '#f8fafc' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                                <strong style={{ fontSize: '13px' }}>Slot #{index + 1}</strong>
                                <Button 
                                    isDestructive 
                                    isSmall 
                                    icon="trash" 
                                    onClick={() => removeSlot(index)}
                                    label={__( 'Delete Slots', 'skaaa-no-code-design' )}
                                />
                            </div>
                            
                            <SelectControl
                                label={__( 'Select Symbol', 'skaaa-no-code-design' )}
                                value={slot.organismId}
                                options={organisms.length > 0 ? organisms : [{label: slot.organismId ? slot.organismId : __( 'Select Symbol...', 'skaaa-no-code-design' ), value: slot.organismId || ''}]}
                                onChange={(val) => updateSlot(index, 'organismId', val)}
                            />
                            
                            <TextControl
                                label={__( 'Conditions (SkaaaFX)', 'skaaa-no-code-design' )}
                                value={slot.condition}
                                onChange={(val) => updateSlot(index, 'condition', val)}
                                help={__( 'Leave blank or type \"default\" for default slot.', 'skaaa-no-code-design' )}
                            />
                        </div>
                    ))}
                    
                    <Button 
                        variant="secondary" 
                        onClick={addSlot}
                        style={{ width: '100%', justifyContent: 'center', marginTop: '8px' }}
                    >
                        {__( '+ Add New Slot', 'skaaa-no-code-design' )}
                    </Button>
                </PanelBody>

                <TailwindPanel
                    className={tailwindClasses || ''}
                    setClassName={(allClasses) => setAttributes({ tailwindClasses: allClasses })}
                />
            </InspectorControls>

            {hasValidConfig ? (
                <div className="skaaa-loop-editor-wrapper" style={{ pointerEvents: 'none', display: 'contents' }}>
                    <ServerSideRender
                        block="skaaaaa-builder/loop"
                        attributes={{ sourceType, sourceTable, postType, category, limit, enablePagination, paginationTargetId, paginationRange, includePrevNext, inheritQuery, slots, tailwindClasses }}
                        httpMethod="POST"
                    />
                </div>
            ) : (
                <Placeholder
                    icon="update"
                    label={__( 'Skaaa Query Loop', 'skaaa-no-code-design' )}
                    instructions={__( 'Please set up a Data Source and at least 1 Slot to display data.', 'skaaa-no-code-design' )}
                >
                    <div style={{ textAlign: 'left', width: '100%', marginTop: '16px' }}>
                        <ul style={{ listStyle: 'disc', paddingLeft: '20px', fontSize: '13px', color: '#475569' }}>
                            <li>{__( 'Data Source: ', 'skaaa-no-code-design' )}<strong>{sourceType === 'pagination' ? `Pagination Loop (${paginationTargetId || 'blog-posts-grid'})` : (sourceType === 'wp_posts' ? `WordPress Posts (${postType})` : (sourceTable || __( 'Not configured', 'skaaa-no-code-design' )))}</strong></li>
                            <li>{__( 'Slots Count: ', 'skaaa-no-code-design' )}<strong>{slots ? slots.length : 0}</strong></li>
                        </ul>
                    </div>
                </Placeholder>
            )}
        </div>
    );
}
