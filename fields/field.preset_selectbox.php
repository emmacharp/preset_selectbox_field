<?php

    require_once FACE . '/interface.exportablefield.php';
    require_once FACE . '/interface.importablefield.php';
    require_once(EXTENSIONS . '/preset_selectbox_field/lib/class.entryquerypresetselectboxadapter.php');

    require_once(EXTENSIONS . '/preset_selectbox_field/extension.driver.php');

    class FieldPreset_selectbox extends Field implements ExportableField, ImportableField {
        public function __construct(){
            parent::__construct();
            $this->entryQueryFieldAdapter = new EntryQueryPresetSelectboxAdapter($this);

            $this->_name = __(extension_preset_selectbox_field::EXT_NAME);
            $this->_required = true;
            $this->_showassociation = false;

            // Set default
            $this->set('show_column', 'yes');
            $this->set('location', 'sidebar');
            $this->set('required', 'no');
        }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

        public function canToggle(){
            return false;
        }

        public function canFilter(){
            return true;
        }

        public function canPrePopulate(){
            return false;
        }

        public function isSortable(){
            return true;
        }

        public function allowDatasourceOutputGrouping(){
            // Grouping follows the same rule as toggling.
            return false;
        }

        public function allowDatasourceParamOutput(){
            return true;
        }

        public function requiresSQLGrouping(){
            return false;
        }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

        const TABLE_NAME = 'tbl_fields_preset_selectbox';

        public function createTable(){
            return Symphony::Database()
                ->create('tbl_entries_data_' . $this->get('id'))
                ->ifNotExists()
                ->fields([
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true,
                    ],
                    'entry_id' => 'int(11)',
                    'handle' => [
                        'type' => 'varchar(255)',
                        'null' => true,
                    ],
                    'value' => [
                        'type' => 'varchar(255)',
                        'null' => true,
                    ],
                ])
                ->keys([
                    'id' => 'primary',
                    'entry_id' => 'key',
                    'handle' => 'key',
                    'value' => 'key',
                ])
                ->execute()
                ->success();
        }

        public static function createFieldTable() {
            return Symphony::Database()
                ->create(self::TABLE_NAME)
                ->ifNotExists()
                ->fields([
                    'id' => [
                        'type' => 'int(11)',
                        'auto' => true,
                    ],
                    'field_id' => 'int(11)',
                    'allow_multiple_selection' => [
                        'type' => 'enum',
                        'values' => ['yes','no'],
                        'default' => 'no',
                    ],
                    'presets' => [
                        'type' => 'varchar(255)',
                        'null' => true,
                    ],
                    'allow_toggle' => [
                        'type' => 'enum',
                        'values' => ['yes','no'],
                        'default' => 'no',
                    ],
                ])
                ->keys([
                    'id' => 'primary',
                    'field_id' => 'unique',
                ])
                ->execute()
                ->success();
        }

        public static function deleteFieldTable() {
            return Symphony::Database()
                ->drop(self::TABLE_NAME)
                ->ifExists()
                ->execute()
                ->success();
        }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/



    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

        public function findDefaults(array &$settings){
            if(!isset($settings['allow_multiple_selection'])) $settings['allow_multiple_selection'] = 'no';
            if(!isset($settings['allow_toggle'])) $settings['allow_toggle'] = 'no';
        }

        public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
            parent::displaySettingsPanel($wrapper, $errors);

            $presets = file_get_contents(EXTENSIONS . '/preset_selectbox_field/presets.json');
            $presets = json_decode($presets);

            $div = new XMLElement('div', NULL, array('class' => ''));

            $label = Widget::Label(__('Preset'));
            $label->setAttribute('class', 'column');

            $options = array();

            if ($presets) {
                foreach ($presets as $key => $preset) {
                    $options[] = array($key, $this->get('presets') === $key, $key);
                }
            } else {
                // TODO: @2k no presets found.
            }

            $input = Widget::Select('fields['.$this->get('sortorder').'][presets]', $options);
            $label->appendChild($input);
            $div->appendChild($label);
            $wrapper->appendChild($div);

            $div = new XMLElement('div', NULL, array('class' => 'two columns'));

            // Allow selection of multiple items
            $label = Widget::Label();
            $label->setAttribute('class', 'column');
            $input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
            if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
            $label->setValue(__('%s Allow selection of multiple options', array($input->generate())));
            $div->appendChild($label);

            // Sort options
            $label = Widget::Label();
            $label->setAttribute('class', 'column');
            $input = Widget::Input('fields['.$this->get('sortorder').'][allow_toggle]', 'yes', 'checkbox');
            if($this->get('allow_toggle') == 'yes') $input->setAttribute('checked', 'checked');
            $label->setValue(__('%s Allow UI Toggle', array($input->generate())));
            $div->appendChild($label);

            $this->appendShowColumnCheckbox($div);
            $this->appendRequiredCheckbox($div);
            $wrapper->appendChild($div);
        }

        public function checkFields(array &$errors, $checkForDuplicates = true){
            if(!is_array($errors)) $errors = array();

            if($this->get('presets') == '') {
                $errors['presets'] = __('The preset setting is required.');
            }

            parent::checkFields($errors, $checkForDuplicates);
        }

        public function commit(){
            if(!parent::commit()) return false;

            $id = $this->get('id');

            if($id === false) return false;

            $fields = array();

            $fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
            $fields['presets'] = $this->get('presets');
            $fields['allow_toggle'] = ($this->get('allow_toggle') ? $this->get('allow_toggle') : 'no');

            return FieldManager::saveSettings($id, $fields);
        }

    /*-------------------------------------------------------------------------
        Publish:
    -------------------------------------------------------------------------*/

        public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
            $presets = file_get_contents(EXTENSIONS . '/preset_selectbox_field/presets.json');
            $presets = json_decode($presets);
            $presets = $presets->{$this->get('presets')};

            $fieldname = 'fields' . $fieldnamePrefix . '['.$this->get('element_name').']' . $fieldnamePostfix;

            if($this->get('allow_multiple_selection') == 'yes') {
                $fieldname .= '[]';
            }

            $label = Widget::Label();
            $fieldLabelCtn = new XMLElement('span', null, array('class' => 'preset-selectbox-label-ctn'));
            $fieldLabel = new XMLElement('span', $this->get('label'));
            $fieldLabelCtn->appendChild($fieldLabel);

            if($this->get('required') != 'yes') {
                $fieldLabelCtn->appendChild(new XMLElement('i', __('Optional')));
            }

            $label->appendChild($fieldLabelCtn);

            $selectCtn = new XMLElement('div', null, array('class' => 'preset-selectbox-wrapper'));
            $select = new XMLElement('div');
            $select->addClass('preset-selectbox-ctn js-preset-selectbox-content');
            $select->setAttribute('data-multiple', $this->get('allow_multiple_selection') == 'yes');
            $savedValues = str_getcsv($data['value']);

            if ($presets) {
                foreach ($presets->values as $key => $value) {
                    $id = $this->get('id') . General::createHandle($value->value);
                    $ctn = new XMLElement('div');
                    $ctn->addClass('preset-selectbox-choice');
                    $lbl = new XMLElement('label');
                    $lbl->setAttribute('for', $id);

                    if (!empty($value->description)) {
                        $lbl->setAttribute('title', $value->description);
                    }

                    if (!empty($value->svg)) {
                        $svg = new XMLElement('span', $value->svg);
                        $svg->addClass('icon');
                        $lbl->appendChild($svg);
                        if ($value->label) {
                            $txtLbl = new XMLElement('span', $value->label);
                            $txtLbl->addClass('text-label');
                            $lbl->appendChild($txtLbl);
                        }
                    } else {
                        $lbl->setValue(!!$value->label ? $value->label : $value->value);
                    }

                    $input = new XMLElement('input');
                    $input->setAttribute('id', $id);
                    $input->setAttribute('name', $fieldname);
                    $input->setAttribute('type', 'checkbox');
                    $input->setAttribute('value', $value->value);

                    if (in_array($value->value, $savedValues)) {
                        $input->setAttribute('checked', 'checked');
                    }

                    $ctn->appendChild($input);
                    $ctn->appendChild($lbl);
                    $select->appendChild($ctn);
                }
            }
            $selectCtn->appendChild($select);
            $label->appendChild($selectCtn);

            if ($this->get('allow_toggle') === 'yes' && $this->get('required') === 'no') {
                $toggleCtn = new XMLElement('label', null, array('class' => 'preset-selectbox-toggle'));
                $toggle = new XMLElement('input', null, array(
                    'type' => 'checkbox',
                    'class' => 'js-preset-selectbox-toggle'
                ));

                if (!empty($data['value'])) {
                    $toggle->setAttribute('checked', 'checked');
                }

                $toggleCtn->appendChild($toggle);
                $icons = new XMLElement('div', null, array('class' => 'icons'));
                $icons->appendChild('<svg class="plus" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 0V10" stroke="currentColor" stroke-width="2"/><path d="M10 5L-4.76837e-07 5" stroke="currentColor" stroke-width="2"/></svg>');
                $icons->appendChild('<svg class="minus" width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>');
                $toggleCtn->appendChild($icons);
                $label->appendChild($toggleCtn);
            }

            if($flagWithError != null) $wrapper->appendChild(Widget::Error($label, $flagWithError));
            else $wrapper->appendChild($label);
        }

        public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
            $status = self::__OK__;
            $result = array();

            if(!is_array($data)) {
                $result['value'] = json_encode($data);
                $result['handle'] = json_encode(Lang::createHandle($data));
            } else {
                foreach($data as $value) {
                    $result['value'][] = json_encode($value);
                    $result['handle'][] = json_encode(Lang::createHandle($value));
                }
                $result['value'] = implode(', ', $result['value']);
                $result['handle'] = implode(', ', $result['handle']);
            }

            return $result;
        }

    /*-------------------------------------------------------------------------
        Output:
    -------------------------------------------------------------------------*/

        public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
            if (!is_array($data) or is_null($data['value'])) return;

            $list = new XMLElement($this->get('element_name'));

            if (!is_array($data['handle']) and !is_array($data['value'])) {
                if ($data['value'] !== 'null') {
                    $data = array(
                        'handle'	=> str_getcsv($data['handle']),
                        'value'		=> str_getcsv($data['value']),
                    );
                } else {
                    $data = array(
                        'value' => array(),
                        'handle' => array(),
                    );
                }
            }

            foreach ($data['value'] as $index => $value) {
                $list->appendChild(new XMLElement(
                    'item',
                    General::sanitize($value),
                    array(
                        'handle'	=> $data['handle'][$index]
                    )
                ));
            }

            if (!empty($list->getNumberOfChildren())) {
                $wrapper->appendChild($list);
            }
        }

        public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
            $value = $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::VALUE, $entry_id);

            return parent::prepareTableValue(array('value' => implode(', ', $value)), $link, $entry_id = null);
        }

        public function getParameterPoolValue(array $data, $entry_id = null) {
            return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::HANDLE, $entry_id);
        }

    /*-------------------------------------------------------------------------
        Import:
    -------------------------------------------------------------------------*/

        public function getImportModes() {
            return array(
                'getValue' =>		ImportableField::STRING_VALUE,
                'getPostdata' =>	ImportableField::ARRAY_VALUE
            );
        }

        public function prepareImportValue($data, $mode, $entry_id = null) {
            $message = $status = null;
            $modes = (object)$this->getImportModes();

            if(!is_array($data)) {
                $data = array($data);
            }

            if($mode === $modes->getValue) {
                if ($this->get('allow_multiple_selection') === 'no') {
                    $data = array(implode('', $data));
                }

                return $data;
            }
            else if($mode === $modes->getPostdata) {
                return $this->processRawFieldData($data, $status, $message, true, $entry_id);
            }

            return null;
        }

    /*-------------------------------------------------------------------------
        Export:
    -------------------------------------------------------------------------*/

        /**
         * Return a list of supported export modes for use with `prepareExportValue`.
         *
         * @return array
         */
        public function getExportModes() {
            return array(
                'listHandle' =>			ExportableField::LIST_OF
                                        + ExportableField::HANDLE,
                'listValue' =>			ExportableField::LIST_OF
                                        + ExportableField::VALUE,
                'listHandleToValue' =>	ExportableField::LIST_OF
                                        + ExportableField::HANDLE
                                        + ExportableField::VALUE,
                'getPostdata' =>		ExportableField::POSTDATA
            );
        }

        /**
         * Give the field some data and ask it to return a value using one of many
         * possible modes.
         *
         * @param mixed $data
         * @param integer $mode
         * @param integer $entry_id
         * @return array
         */
        public function prepareExportValue($data, $mode, $entry_id = null) {
            $modes = (object)$this->getExportModes();

            if (isset($data['handle']) && is_array($data['handle']) === false) {
                $data['handle'] = array(
                    $data['handle']
                );
            }

            if (isset($data['value']) && is_array($data['value']) === false) {
                $data['value'] = array(
                    $data['value']
                );
            }

            // Handle => Value pairs:
            if ($mode === $modes->listHandleToValue) {
                return isset($data['handle'], $data['value'])
                    ? array_combine($data['handle'], $data['value'])
                    : array();
            }

            // Array of handles:
            else if ($mode === $modes->listHandle) {
                return isset($data['handle'])
                    ? $data['handle']
                    : array();
            }

            // Array of values:
            else if ($mode === $modes->listValue || $mode === $modes->getPostdata) {
                return isset($data['value'])
                    ? $data['value']
                    : array();
            }
        }

    /*-------------------------------------------------------------------------
        Filtering:
    -------------------------------------------------------------------------*/

        public function displayDatasourceFilterPanel(XMLElement &$wrapper, $data = null, $errors = null, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
            parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);

            $data = preg_split('/,\s*/i', $data);
            $data = array_map('trim', $data);

            $existing_options = $this->getToggleStates();

            if(is_array($existing_options) && !empty($existing_options)){
                $optionlist = new XMLElement('ul');
                $optionlist->setAttribute('class', 'tags');

                foreach($existing_options as $option) {
                    $optionlist->appendChild(
                        new XMLElement('li', General::sanitize($option))
                    );
                };

                $wrapper->appendChild($optionlist);
            }
        }

        public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
            $field_id = $this->get('id');

            if (self::isFilterRegex($data[0])) {
                $this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
            }
            else if ($andOperation) {
                foreach ($data as $value) {
                    $this->_key++;
                    $value = $this->cleanValue($value);
                    $joins .= "
                        LEFT JOIN
                            `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                            ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                    ";
                    $where .= "
                        AND (
                            t{$field_id}_{$this->_key}.value = '{$value}'
                            OR t{$field_id}_{$this->_key}.handle = '{$value}'
                        )
                    ";
                }
            }
            else {
                if (!is_array($data)) $data = array($data);

                foreach ($data as &$value) {
                    $value = $this->cleanValue($value);
                }

                $this->_key++;
                $data = implode("', '", $data);
                $joins .= "
                    LEFT JOIN
                        `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                        ON (e.id = t{$field_id}_{$this->_key}.entry_id)
                ";
                $where .= "
                    AND (
                        t{$field_id}_{$this->_key}.value IN ('{$data}')
                        OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
                    )
                ";
            }

            return true;
        }

    /*-------------------------------------------------------------------------
        Grouping:
    -------------------------------------------------------------------------*/

        public function groupRecords($records){
            if(!is_array($records) || empty($records)) return;

            $groups = array($this->get('element_name') => array());

            foreach($records as $r){
                $data = $r->getData($this->get('id'));
                $value = General::sanitize($data['value']);

                if(!isset($groups[$this->get('element_name')][$data['handle']])){
                    $groups[$this->get('element_name')][$data['handle']] = array(
                        'attr' => array('handle' => $data['handle'], 'value' => $value),
                        'records' => array(),
                        'groups' => array()
                    );
                }

                $groups[$this->get('element_name')][$data['handle']]['records'][] = $r;
            }

            return $groups;
        }

    }
