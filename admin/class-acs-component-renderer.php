<?php
/**
 * Component Renderer for ACS Admin Interface
 *
 * Provides reusable UI components with consistent styling and behavior
 * following the BEM methodology and design system patterns.
 *
 * @package AI_Content_Studio
 * @subpackage Admin
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ACS_Component_Renderer
 *
 * Renders reusable UI components for the admin interface
 */
class ACS_Component_Renderer {
    
    /**
     * Render a card component
     *
     * @param array $props Card properties
     * @return string HTML output
     */
    public function render_card($props) {
        $defaults = [
            'title' => '',
            'content' => '',
            'variant' => 'default', // default, stat, action, status, activity
            'size' => 'medium', // small, medium, large
            'actions' => [],
            'loading' => false,
            'classes' => []
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $classes = ['acs-card', 'acs-card--' . $props['variant'], 'acs-card--' . $props['size']];
        if ($props['loading']) {
            $classes[] = 'acs-card--loading';
        }
        if (!empty($props['classes'])) {
            $classes = array_merge($classes, $props['classes']);
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        
        if (!empty($props['title'])) {
            $html .= '<div class="acs-card__header">';
            $html .= '<h3 class="acs-card__title">' . esc_html($props['title']) . '</h3>';
            $html .= '</div>';
        }
        
        $html .= '<div class="acs-card__content">';
        $html .= $props['content']; // Content should be pre-escaped
        $html .= '</div>';
        
        if (!empty($props['actions'])) {
            $html .= '<div class="acs-card__actions">';
            foreach ($props['actions'] as $action) {
                $html .= $this->render_button($action);
            }
            $html .= '</div>';
        }
        
        if ($props['loading']) {
            $html .= '<div class="acs-card__loading">';
            $html .= '<div class="acs-spinner"></div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a button component using WordPress button classes
     *
     * @param array $props Button properties
     * @return string HTML output
     */
    public function render_button($props) {
        $defaults = [
            'text' => '',
            'url' => '',
            'variant' => 'primary', // primary, secondary, danger, ghost
            'size' => 'medium', // small, medium, large
            'icon' => '',
            'disabled' => false,
            'loading' => false,
            'attributes' => [],
            'use_wp_classes' => true, // Use WordPress button classes by default
            'aria_label' => '', // Custom ARIA label
            'aria_describedby' => '', // ARIA described by
            'role' => 'button' // ARIA role
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        // Use WordPress button classes when enabled
        $classes = [];
        if ($props['use_wp_classes']) {
            $classes[] = 'button';
            
            // WordPress button variants
            switch ($props['variant']) {
                case 'primary':
                    $classes[] = 'button-primary';
                    break;
                case 'secondary':
                    $classes[] = 'button-secondary';
                    break;
                case 'danger':
                    $classes[] = 'button-secondary';
                    $classes[] = 'acs-button-danger'; // Custom class for danger styling
                    break;
                case 'ghost':
                    $classes[] = 'button-link';
                    break;
                default:
                    $classes[] = 'button-secondary';
            }
            
            // WordPress button sizes
            switch ($props['size']) {
                case 'small':
                    $classes[] = 'button-small';
                    break;
                case 'large':
                    $classes[] = 'button-large';
                    break;
                case 'hero':
                    $classes[] = 'button-hero';
                    break;
            }
        } else {
            $classes = ['acs-button', 'acs-button--' . $props['variant'], 'acs-button--' . $props['size']];
        }
        
        if ($props['disabled']) {
            $classes[] = $props['use_wp_classes'] ? 'disabled' : 'acs-button--disabled';
        }
        if ($props['loading']) {
            $classes[] = $props['use_wp_classes'] ? 'updating-message' : 'acs-button--loading';
        }
        
        $attributes = [
            'class' => implode(' ', $classes)
        ];
        
        // Accessibility attributes
        if (!empty($props['aria_label'])) {
            $attributes['aria-label'] = $props['aria_label'];
        }
        
        if (!empty($props['aria_describedby'])) {
            $attributes['aria-describedby'] = $props['aria_describedby'];
        }
        
        if (!empty($props['role']) && $props['role'] !== 'button') {
            $attributes['role'] = $props['role'];
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
            $attributes['aria-disabled'] = 'true';
        }
        
        if ($props['loading']) {
            $attributes['aria-busy'] = 'true';
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        if (!empty($props['url'])) {
            $html = '<a href="' . esc_url($props['url']) . '"' . $attr_string . '>';
        } else {
            $html = '<button type="button"' . $attr_string . '>';
        }
        
        if (!empty($props['icon'])) {
            $html .= '<span class="dashicons dashicons-' . esc_attr($props['icon']) . '" aria-hidden="true"></span>';
        }
        
        $html .= '<span class="button-text">' . esc_html($props['text']) . '</span>';
        
        if ($props['loading']) {
            if ($props['use_wp_classes']) {
                $html .= '<span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>';
            } else {
                $html .= '<span class="acs-button__spinner acs-spinner"></span>';
            }
        }
        
        if (!empty($props['url'])) {
            $html .= '</a>';
        } else {
            $html .= '</button>';
        }
        
        return $html;
    }
    
    /**
     * Render a table component
     *
     * @param array $props Table properties
     * @return string HTML output
     */
    public function render_table($props) {
        $defaults = [
            'columns' => [],
            'data' => [],
            'sortable' => false,
            'filterable' => false,
            'bulk_actions' => [],
            'pagination' => false,
            'classes' => []
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $classes = ['acs-table', 'wp-list-table', 'widefat', 'fixed', 'striped'];
        if (!empty($props['classes'])) {
            $classes = array_merge($classes, $props['classes']);
        }
        
        $html = '<div class="acs-table-wrapper">';
        
        if ($props['filterable'] || !empty($props['bulk_actions'])) {
            $html .= '<div class="acs-table__controls">';
            
            if ($props['filterable']) {
                $html .= '<div class="acs-table__filters">';
                $html .= '<input type="search" class="acs-table__search" placeholder="' . esc_attr__('Search...', 'ai-content-studio') . '">';
                $html .= '</div>';
            }
            
            if (!empty($props['bulk_actions'])) {
                $html .= '<div class="acs-table__bulk-actions">';
                $html .= '<select class="acs-table__bulk-select">';
                $html .= '<option value="">' . esc_html__('Bulk Actions', 'ai-content-studio') . '</option>';
                foreach ($props['bulk_actions'] as $action) {
                    $html .= '<option value="' . esc_attr($action['value']) . '">' . esc_html($action['label']) . '</option>';
                }
                $html .= '</select>';
                $html .= '<button type="button" class="button acs-table__bulk-apply">' . esc_html__('Apply', 'ai-content-studio') . '</button>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '<table class="' . esc_attr(implode(' ', $classes)) . '">';
        
        // Table header
        if (!empty($props['columns'])) {
            $html .= '<thead><tr>';
            
            if (!empty($props['bulk_actions'])) {
                $html .= '<td class="check-column"><input type="checkbox" class="acs-table__select-all"></td>';
            }
            
            foreach ($props['columns'] as $column) {
                $column_classes = ['acs-table__header'];
                if ($props['sortable'] && !empty($column['sortable'])) {
                    $column_classes[] = 'acs-table__header--sortable';
                }
                
                $html .= '<th class="' . esc_attr(implode(' ', $column_classes)) . '"';
                if (!empty($column['data_key'])) {
                    $html .= ' data-key="' . esc_attr($column['data_key']) . '"';
                }
                $html .= '>';
                $html .= esc_html($column['label']);
                if ($props['sortable'] && !empty($column['sortable'])) {
                    $html .= '<span class="acs-table__sort-indicator"></span>';
                }
                $html .= '</th>';
            }
            
            $html .= '</tr></thead>';
        }
        
        // Table body
        $html .= '<tbody>';
        if (!empty($props['data'])) {
            foreach ($props['data'] as $row) {
                $html .= '<tr>';
                
                if (!empty($props['bulk_actions'])) {
                    $html .= '<th class="check-column">';
                    $html .= '<input type="checkbox" class="acs-table__select-item" value="' . esc_attr($row['id'] ?? '') . '">';
                    $html .= '</th>';
                }
                
                foreach ($props['columns'] as $column) {
                    $html .= '<td>';
                    if (isset($row[$column['data_key']])) {
                        if (!empty($column['render_callback']) && is_callable($column['render_callback'])) {
                            $html .= call_user_func($column['render_callback'], $row[$column['data_key']], $row);
                        } else {
                            $html .= esc_html($row[$column['data_key']]);
                        }
                    }
                    $html .= '</td>';
                }
                
                $html .= '</tr>';
            }
        } else {
            $colspan = count($props['columns']);
            if (!empty($props['bulk_actions'])) {
                $colspan++;
            }
            $html .= '<tr><td colspan="' . $colspan . '">' . esc_html__('No data available.', 'ai-content-studio') . '</td></tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        
        if ($props['pagination']) {
            $html .= '<div class="acs-table__pagination">';
            // Pagination controls would go here
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render an accessible progress indicator
     *
     * @param array $props Progress properties
     * @return string HTML output
     */
    public function render_progress($props) {
        $defaults = [
            'value' => 0,
            'max' => 100,
            'label' => '',
            'show_percentage' => true,
            'variant' => 'default', // success, warning, error, default
            'size' => 'medium', // small, medium, large
            'color_variant' => 'default' // For color-based progress indicators
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $percentage = $props['max'] > 0 ? ($props['value'] / $props['max']) * 100 : 0;
        $classes = ['acs-progress', 'acs-progress--' . $props['variant'], 'acs-progress--' . $props['size']];
        
        // Add color variant class if specified
        if (!empty($props['color_variant']) && $props['color_variant'] !== 'default') {
            $classes[] = 'acs-progress--' . $props['color_variant'];
        }
        
        $attributes = [
            'class' => implode(' ', $classes),
            'role' => 'progressbar',
            'aria-valuenow' => $props['value'],
            'aria-valuemin' => '0',
            'aria-valuemax' => $props['max']
        ];
        
        if (!empty($props['label'])) {
            $attributes['aria-label'] = $props['label'];
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $html = '<div' . $attr_string . '>';
        $html .= '<div class="acs-progress__track">';
        $html .= '<div class="acs-progress__fill" style="width: ' . esc_attr($percentage) . '%"></div>';
        $html .= '</div>';
        
        if ($props['show_percentage'] || !empty($props['label'])) {
            $html .= '<div class="acs-progress__label">';
            if (!empty($props['label'])) {
                $html .= '<span class="acs-progress__text">' . esc_html($props['label']) . '</span>';
            }
            if ($props['show_percentage']) {
                $html .= '<span class="acs-progress__percentage">' . number_format($percentage, 1) . '%</span>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render circular progress indicator
     *
     * @param array $props Progress properties
     * @param float $percentage Calculated percentage
     * @return string HTML output
     */
    private function render_circular_progress($props, $percentage) {
        $classes = ['acs-progress', 'acs-progress--circular', 'acs-progress--' . $props['size']];
        
        $radius = 45;
        $circumference = 2 * pi() * $radius;
        $offset = $circumference - ($percentage / 100) * $circumference;
        
        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        $html .= '<svg class="acs-progress__circle" width="100" height="100">';
        $html .= '<circle class="acs-progress__track-circle" cx="50" cy="50" r="' . $radius . '"></circle>';
        $html .= '<circle class="acs-progress__fill-circle" cx="50" cy="50" r="' . $radius . '" ';
        $html .= 'style="stroke-dasharray: ' . $circumference . '; stroke-dashoffset: ' . $offset . '"></circle>';
        $html .= '</svg>';
        
        if ($props['show_percentage']) {
            $html .= '<div class="acs-progress__percentage">' . number_format($percentage, 1) . '%</div>';
        }
        
        if (!empty($props['label'])) {
            $html .= '<div class="acs-progress__label">' . esc_html($props['label']) . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render an accessible badge component
     *
     * @param array $props Badge properties
     * @return string HTML output
     */
    public function render_badge($props) {
        $defaults = [
            'text' => '',
            'variant' => 'default', // success, warning, error, info, default
            'size' => 'medium', // small, medium, large
            'icon' => '',
            'aria_label' => '', // Custom ARIA label for screen readers
            'role' => 'status' // ARIA role
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $classes = ['acs-badge', 'acs-badge--' . $props['variant'], 'acs-badge--' . $props['size']];
        
        $attributes = [
            'class' => implode(' ', $classes),
            'role' => $props['role']
        ];
        
        // Add ARIA label if provided, otherwise use text content
        if (!empty($props['aria_label'])) {
            $attributes['aria-label'] = $props['aria_label'];
        } else if (!empty($props['text'])) {
            $attributes['aria-label'] = sprintf(__('Status: %s', 'ai-content-studio'), $props['text']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $html = '<span' . $attr_string . '>';
        
        if (!empty($props['icon'])) {
            $html .= '<span class="dashicons dashicons-' . esc_attr($props['icon']) . '" aria-hidden="true"></span>';
        }
        
        $html .= '<span class="acs-badge__text">' . esc_html($props['text']) . '</span>';
        $html .= '</span>';
        
        return $html;
    }
    
    /**
     * Render a notification/alert using WordPress admin notice patterns
     *
     * @param array $props Alert properties
     * @return string HTML output
     */
    public function render_alert($props) {
        $defaults = [
            'message' => '',
            'type' => 'info', // success, warning, error, info
            'dismissible' => true,
            'actions' => [],
            'use_wp_classes' => true // Use WordPress notice classes by default
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        if ($props['use_wp_classes']) {
            return $this->render_wordpress_notice($props);
        }
        
        // Fallback to custom alert styling
        $classes = ['acs-alert', 'acs-alert--' . $props['type']];
        if ($props['dismissible']) {
            $classes[] = 'acs-alert--dismissible';
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        $html .= '<div class="acs-alert__content">';
        $html .= '<p>' . wp_kses_post($props['message']) . '</p>';
        
        if (!empty($props['actions'])) {
            $html .= '<div class="acs-alert__actions">';
            foreach ($props['actions'] as $action) {
                $html .= $this->render_button($action);
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        if ($props['dismissible']) {
            $html .= '<button type="button" class="acs-alert__dismiss" aria-label="' . esc_attr__('Dismiss', 'ai-content-studio') . '">';
            $html .= '<span class="dashicons dashicons-dismiss"></span>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render WordPress-style admin notice
     *
     * @param array $props Notice properties
     * @return string HTML output
     */
    public function render_wordpress_notice($props) {
        $classes = ['notice'];
        
        // WordPress notice types
        switch ($props['type']) {
            case 'success':
                $classes[] = 'notice-success';
                break;
            case 'error':
                $classes[] = 'notice-error';
                break;
            case 'warning':
                $classes[] = 'notice-warning';
                break;
            case 'info':
            default:
                $classes[] = 'notice-info';
                break;
        }
        
        if ($props['dismissible']) {
            $classes[] = 'is-dismissible';
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        $html .= '<p>' . wp_kses_post($props['message']) . '</p>';
        
        if (!empty($props['actions'])) {
            $html .= '<p>';
            foreach ($props['actions'] as $action) {
                // Ensure WordPress button styling for notice actions
                $action['use_wp_classes'] = true;
                $action['variant'] = $action['variant'] ?? 'secondary';
                $html .= $this->render_button($action) . ' ';
            }
            $html .= '</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render breadcrumb navigation
     *
     * @param array $items Breadcrumb items
     * @return string HTML output
     */
    public function render_breadcrumbs($items) {
        if (empty($items)) {
            return '';
        }
        
        $html = '<nav class="acs-breadcrumbs active" aria-label="' . esc_attr__('Breadcrumb', 'ai-content-studio') . '">';
        $html .= '<ol class="acs-breadcrumbs__list">';
        
        foreach ($items as $index => $item) {
            $is_last = ($index === count($items) - 1);
            
            $item_class = 'acs-breadcrumbs__item';
            if ($is_last) {
                $item_class .= ' active';
            }
            
            $html .= '<li class="' . esc_attr($item_class) . '">';
            
            if (!$is_last && !empty($item['url'])) {
                $html .= '<a href="' . esc_url($item['url']) . '" class="acs-breadcrumbs__link">';
                $html .= esc_html($item['label']);
                $html .= '</a>';
            } else {
                $html .= '<span class="acs-breadcrumbs__current active" aria-current="page">';
                $html .= esc_html($item['label']);
                $html .= '</span>';
            }
            
            if (!$is_last) {
                $html .= '<span class="acs-breadcrumbs__separator" aria-hidden="true">/</span>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Render back button navigation helper
     *
     * @param array $props Back button properties
     * @return string HTML output
     */
    public function render_back_button($props) {
        $defaults = [
            'text' => __('Back', 'ai-content-studio'),
            'url' => '',
            'icon' => 'arrow-left-alt2'
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $html = '<div class="acs-back-button">';
        $html .= '<a href="' . esc_url($props['url']) . '" class="acs-nav-helper" data-acs-back="' . esc_attr($props['url']) . '">';
        $html .= '<span class="dashicons dashicons-' . esc_attr($props['icon']) . '"></span>';
        $html .= '<span>' . esc_html($props['text']) . '</span>';
        $html .= '</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render navigation shortcuts hint
     *
     * @return string HTML output
     */
    public function render_shortcuts_hint() {
        $shortcuts = [
            ['keys' => 'Alt + D', 'label' => __('Dashboard', 'ai-content-studio')],
            ['keys' => 'Alt + G', 'label' => __('Generate', 'ai-content-studio')],
            ['keys' => 'Alt + A', 'label' => __('Analytics', 'ai-content-studio')],
            ['keys' => 'Alt + S', 'label' => __('Settings', 'ai-content-studio')]
        ];
        
        $html = '<div class="acs-shortcuts-hint" id="acs-shortcuts-hint">';
        $html .= '<strong>' . esc_html__('Keyboard Shortcuts:', 'ai-content-studio') . '</strong><br>';
        
        foreach ($shortcuts as $shortcut) {
            $keys = explode(' + ', $shortcut['keys']);
            $html .= '<div style="margin-top: 4px;">';
            foreach ($keys as $key) {
                $html .= '<kbd>' . esc_html($key) . '</kbd>';
                if ($key !== end($keys)) {
                    $html .= ' + ';
                }
            }
            $html .= ' ' . esc_html($shortcut['label']);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a form field component using WordPress form helper classes
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    public function render_form_field($props) {
        $defaults = [
            'type' => 'text',
            'name' => '',
            'id' => '',
            'label' => '',
            'value' => '',
            'placeholder' => '',
            'description' => '',
            'required' => false,
            'disabled' => false,
            'readonly' => false,
            'options' => [], // For select, radio, checkbox groups
            'validation' => [],
            'classes' => [],
            'attributes' => [],
            'use_wp_classes' => true // Use WordPress form classes by default
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        // Generate ID if not provided
        if (empty($props['id']) && !empty($props['name'])) {
            $props['id'] = sanitize_title($props['name']);
        }
        
        // Use WordPress form classes when enabled
        $field_classes = ['acs-form-field'];
        if ($props['use_wp_classes']) {
            $field_classes[] = 'form-field';
            $field_classes[] = 'form-field-' . $props['type'];
        } else {
            $field_classes[] = 'acs-form-field--' . $props['type'];
        }
        
        if ($props['required']) {
            $field_classes[] = $props['use_wp_classes'] ? 'form-required' : 'acs-form-field--required';
        }
        if ($props['disabled']) {
            $field_classes[] = $props['use_wp_classes'] ? 'form-disabled' : 'acs-form-field--disabled';
        }
        if (!empty($props['classes'])) {
            $field_classes = array_merge($field_classes, $props['classes']);
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $field_classes)) . '">';
        
        // Label using WordPress patterns
        if (!empty($props['label'])) {
            $label_classes = $props['use_wp_classes'] ? 'form-label' : 'acs-form-label';
            $html .= '<label for="' . esc_attr($props['id']) . '" class="' . $label_classes . '">';
            $html .= esc_html($props['label']);
            if ($props['required']) {
                $html .= ' <span class="required" aria-label="' . esc_attr__('required', 'ai-content-studio') . '">*</span>';
            }
            $html .= '</label>';
        }
        
        // Field wrapper using WordPress patterns
        $wrapper_classes = $props['use_wp_classes'] ? 'form-input-wrapper' : 'acs-form-field-wrapper';
        $html .= '<div class="' . $wrapper_classes . '">';
        
        // Render field based on type
        switch ($props['type']) {
            case 'select':
                $html .= $this->render_select_field($props);
                break;
            case 'textarea':
                $html .= $this->render_textarea_field($props);
                break;
            case 'checkbox':
                $html .= $this->render_checkbox_field($props);
                break;
            case 'radio':
                $html .= $this->render_radio_field($props);
                break;
            case 'file':
                $html .= $this->render_file_field($props);
                break;
            default:
                $html .= $this->render_input_field($props);
                break;
        }
        
        $html .= '</div>';
        
        // Description using WordPress help text patterns
        if (!empty($props['description'])) {
            $desc_classes = $props['use_wp_classes'] ? 'description' : 'acs-form-description';
            $html .= '<p class="' . $desc_classes . '">' . wp_kses_post($props['description']) . '</p>';
        }
        
        // Validation message container using WordPress error patterns
        $validation_classes = $props['use_wp_classes'] ? 'form-invalid' : 'acs-form-validation';
        $html .= '<div class="' . $validation_classes . '" id="' . esc_attr($props['id']) . '-validation" style="display: none;"></div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render input field using WordPress form classes
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_input_field($props) {
        // Use WordPress form input classes
        $input_classes = [];
        if ($props['use_wp_classes'] ?? true) {
            // WordPress standard input classes
            switch ($props['type']) {
                case 'text':
                case 'email':
                case 'url':
                case 'password':
                case 'number':
                    $input_classes[] = 'regular-text';
                    break;
                case 'search':
                    $input_classes[] = 'wp-filter-search';
                    break;
                default:
                    $input_classes[] = 'regular-text';
            }
        } else {
            $input_classes[] = 'acs-form-input';
        }
        
        $attributes = [
            'type' => $props['type'],
            'name' => $props['name'],
            'id' => $props['id'],
            'class' => implode(' ', $input_classes),
            'value' => $props['value']
        ];
        
        if (!empty($props['placeholder'])) {
            $attributes['placeholder'] = $props['placeholder'];
        }
        
        if ($props['required']) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        if ($props['readonly']) {
            $attributes['readonly'] = 'readonly';
        }
        
        // Add WordPress validation attributes
        if (!empty($props['validation'])) {
            foreach ($props['validation'] as $rule => $value) {
                switch ($rule) {
                    case 'minlength':
                        $attributes['minlength'] = $value;
                        break;
                    case 'maxlength':
                        $attributes['maxlength'] = $value;
                        break;
                    case 'pattern':
                        $attributes['pattern'] = $value;
                        break;
                    case 'min':
                        $attributes['min'] = $value;
                        break;
                    case 'max':
                        $attributes['max'] = $value;
                        break;
                }
            }
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        return '<input' . $attr_string . '>';
    }
    
    /**
     * Render select field using WordPress form classes
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_select_field($props) {
        // Use WordPress form select classes
        $select_classes = [];
        if ($props['use_wp_classes'] ?? true) {
            $select_classes[] = 'postform'; // WordPress standard select class
        } else {
            $select_classes[] = 'acs-form-select';
        }
        
        $attributes = [
            'name' => $props['name'],
            'id' => $props['id'],
            'class' => implode(' ', $select_classes)
        ];
        
        if ($props['required']) {
            $attributes['required'] = 'required';
            $attributes['aria-required'] = 'true';
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $html = '<select' . $attr_string . '>';
        
        if (!empty($props['placeholder'])) {
            $html .= '<option value="">' . esc_html($props['placeholder']) . '</option>';
        }
        
        foreach ($props['options'] as $option_value => $option_label) {
            $selected = selected($props['value'], $option_value, false);
            $html .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>';
            $html .= esc_html($option_label);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * Render textarea field
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_textarea_field($props) {
        $textarea_classes = ['acs-form-textarea'];
        
        $attributes = [
            'name' => $props['name'],
            'id' => $props['id'],
            'class' => implode(' ', $textarea_classes)
        ];
        
        if (!empty($props['placeholder'])) {
            $attributes['placeholder'] = $props['placeholder'];
        }
        
        if ($props['required']) {
            $attributes['required'] = 'required';
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        if ($props['readonly']) {
            $attributes['readonly'] = 'readonly';
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        return '<textarea' . $attr_string . '>' . esc_textarea($props['value']) . '</textarea>';
    }
    
    /**
     * Render checkbox field
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_checkbox_field($props) {
        $checkbox_classes = ['acs-form-checkbox'];
        
        $attributes = [
            'type' => 'checkbox',
            'name' => $props['name'],
            'id' => $props['id'],
            'class' => implode(' ', $checkbox_classes),
            'value' => '1'
        ];
        
        if ($props['value']) {
            $attributes['checked'] = 'checked';
        }
        
        if ($props['required']) {
            $attributes['required'] = 'required';
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $html = '<div class="acs-checkbox-wrapper">';
        $html .= '<input' . $attr_string . '>';
        $html .= '<span class="acs-checkbox-checkmark"></span>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render radio field group
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_radio_field($props) {
        $html = '<div class="acs-radio-group">';
        
        foreach ($props['options'] as $option_value => $option_label) {
            $radio_id = $props['id'] . '_' . sanitize_title($option_value);
            $checked = checked($props['value'], $option_value, false);
            
            $html .= '<div class="acs-radio-wrapper">';
            $html .= '<input type="radio" name="' . esc_attr($props['name']) . '" ';
            $html .= 'id="' . esc_attr($radio_id) . '" ';
            $html .= 'value="' . esc_attr($option_value) . '" ';
            $html .= 'class="acs-form-radio"' . $checked;
            
            if ($props['required']) {
                $html .= ' required="required"';
            }
            
            if ($props['disabled']) {
                $html .= ' disabled="disabled"';
            }
            
            $html .= '>';
            $html .= '<span class="acs-radio-checkmark"></span>';
            $html .= '<label for="' . esc_attr($radio_id) . '" class="acs-radio-label">';
            $html .= esc_html($option_label);
            $html .= '</label>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render file field
     *
     * @param array $props Field properties
     * @return string HTML output
     */
    private function render_file_field($props) {
        $file_classes = ['acs-form-file'];
        
        $attributes = [
            'type' => 'file',
            'name' => $props['name'],
            'id' => $props['id'],
            'class' => implode(' ', $file_classes)
        ];
        
        if ($props['required']) {
            $attributes['required'] = 'required';
        }
        
        if ($props['disabled']) {
            $attributes['disabled'] = 'disabled';
        }
        
        if (!empty($props['attributes'])) {
            $attributes = array_merge($attributes, $props['attributes']);
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $html = '<div class="acs-file-wrapper">';
        $html .= '<input' . $attr_string . '>';
        $html .= '<label for="' . esc_attr($props['id']) . '" class="acs-file-label">';
        $html .= '<span class="acs-file-button">' . esc_html__('Choose File', 'ai-content-studio') . '</span>';
        $html .= '<span class="acs-file-name">' . esc_html__('No file chosen', 'ai-content-studio') . '</span>';
        $html .= '</label>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render a form group with multiple fields
     *
     * @param array $props Form group properties
     * @return string HTML output
     */
    public function render_form_group($props) {
        $defaults = [
            'title' => '',
            'description' => '',
            'fields' => [],
            'layout' => 'vertical', // vertical, horizontal, grid
            'classes' => []
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $group_classes = ['acs-form-group', 'acs-form-group--' . $props['layout']];
        if (!empty($props['classes'])) {
            $group_classes = array_merge($group_classes, $props['classes']);
        }
        
        $html = '<div class="' . esc_attr(implode(' ', $group_classes)) . '">';
        
        if (!empty($props['title'])) {
            $html .= '<h3 class="acs-form-group-title">' . esc_html($props['title']) . '</h3>';
        }
        
        if (!empty($props['description'])) {
            $html .= '<div class="acs-form-group-description">' . wp_kses_post($props['description']) . '</div>';
        }
        
        $html .= '<div class="acs-form-group-fields">';
        
        foreach ($props['fields'] as $field) {
            $html .= $this->render_form_field($field);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render an accessible link component
     *
     * @param array $props Link properties
     * @return string HTML output
     */
    public function render_link($props) {
        $defaults = [
            'text' => '',
            'url' => '#',
            'variant' => 'default', // primary, secondary, danger, default
            'target' => '_self',
            'aria_label' => '',
            'title' => '',
            'classes' => []
        ];
        
        $props = wp_parse_args($props, $defaults);
        
        $classes = ['acs-link', 'acs-link--' . $props['variant']];
        if (!empty($props['classes'])) {
            $classes = array_merge($classes, $props['classes']);
        }
        
        $attributes = [
            'href' => $props['url'],
            'class' => implode(' ', $classes),
            'target' => $props['target']
        ];
        
        if (!empty($props['aria_label'])) {
            $attributes['aria-label'] = $props['aria_label'];
        }
        
        if (!empty($props['title'])) {
            $attributes['title'] = $props['title'];
        }
        
        if ($props['target'] === '_blank') {
            $attributes['rel'] = 'noopener noreferrer';
        }
        
        $attr_string = '';
        foreach ($attributes as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        return '<a' . $attr_string . '>' . esc_html($props['text']) . '</a>';
    }
}