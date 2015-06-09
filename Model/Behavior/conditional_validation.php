<?php
/**
 * This behavior allows you to have validation rules for individual fields that only run when you want them to.
 * For example you don't always need to validate file sizes and extensions, unless a file has actually been specified.
 * It works by checking Model->validate->rules->field->rules for a special key ('if')
 * If that key exists as one of the field's rule parameters, then this behaviour expects that it will contain either a single condition
 * or an array of conditions that will be used to determine if we need the validation rule to actually run
 *
 * e.g. when creating validation rules, add the following to the rules array
 * [code]
 * 'category_csv_field' => array(
 *      'isSet'         => array(
 *          'rule'      => 'notEmpty',
 *          'message'   => 'Category CSV Field must be mapped.',
 *          'if'        => array('has_categories', '1'),
 *          )
 *      ),
 * [/code]
 * The above will cause the 'isSet' rule for the field 'category_csv_field' to only run if the 'has_categories' field has a value of '1'
 *
 * @example 'if' => array('has_categories')                 // will run rule iif 'has_categories' exists and != ''
 * @example 'if' => array('has_categories', '1')            // will run rule iif 'has_categories' exists and == '1'
 * @example 'if' => array('has_categories', '1', '>=')      // will run rule iif 'has_categories' exists and >= '1'
 * @example 'if' => array(                                  // multiple conditions
 *                          array('type', 'file'),          //      if type = file
 *                          array('overwrite_file', '1'),   // AND  if overwrite_file = true
 * @example 'if' => array('data.has_categories')            // also supports Hash maps (@see Set::classicExtract)
 */
class ConditionalValidationBehavior extends ModelBehavior
{
    
    /**
     * ModelBehavior setup() function
     *
     * Sets the per-model settings for the behavior
     *
     * @param Model &$Model
     * @param array $settings
     * @return void
     */
    public function setup(Model &$Model, $settings)
    {
        if (!isset($this->settings[$Model->alias])) {
            $this->settings[$Model->alias] = array(
                'key' => 'if',
                );
        }
        $this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
    }

    /**
     * CakePHP Model::beforeValidate() hook
     * This function gets called before Validation occurs.
     *
     * see comments on this class's declaration (above) for what we're doing in here
     * @todo add support for mulitple conditions using OR (currently all multiple conditions are AND)
     * @todo add support for Hash paths outside of the current model (e.g. OtherModel.field should be useable)
     * @return bool true if validation can start, false otherwise
     */
    public function beforeValidate(Model &$Model)
    {
        # look through the rules and see if there are fields that should only validate if thier parent field is set
        foreach ($Model->validate as $field => $rules) :
            if (is_array($rules)) :
                foreach ($rules as $ruleName => $rule) :
                    if (isset($rule[$this->settings[$Model->alias]['key']])) :
                        $keepRule = true;

                        $iifRules = $rule[$this->settings[$Model->alias]['key']];                   # get the 'if' array for this field->rule
                        $iifRules = (is_array($iifRules[0])) ? $iifRules : array($iifRules);        # if it's already an array of arrays, great. otherwise, make it one (makes multiple conditions possible)
                        
                        foreach ($iifRules as $iifRule) :                                           # loop through each condition to see if the rule should be kept
                            if (!isset($iifRule[1])) {                                              # if only 1 param, check that field is set
                                $iifRule[1] = '';
                                $iifRule[2] = '<>';
                            }
                            if (!isset($iifRule[2])) {                                              # if only 2 params, check that field matches value
                                $iifRule[2] = '==';
                            }
                                                                                                    # now, with 3 params, use third as comparator for first two)
                            # keep if   prev OK   &&          conditional field exists                &&           conditional field's value matches what we want
                            $keepRule = $keepRule && (Set::check($Model->data[$Model->alias], $iifRule[0]) && version_compare(Set::classicExtract($Model->data[$Model->alias], $iifRule[0]), $iifRule[1], $iifRule[2]));

                        endforeach;

                        if (!$keepRule) :                                     # if not all the conditions were met (we checked above)
                            unset($Model->validate[$field][$ruleName]);     # then remove this rule, we don't want it to run
                        endif;

                    endif;
                endforeach;
            endif;
        endforeach;
        return true;
    }
}
