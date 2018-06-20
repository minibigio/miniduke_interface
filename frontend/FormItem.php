<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 19/03/2018
 * Time: 09:56
 */

?>

<?php
class FormItem {

    protected $json, $type, $name, $label, $val;

    public static $form_index = 0;

    public function __construct($json) {
        $this->json = $json;
        self::$form_index++;
        $this->decode_json();
    }


    private function decode_json() {

        if (isset($this->json['type']) && $this->json['type'] != null) {
            $this->type = $this->json['type'];
            if ($this->json['type'] == 'select' || $this->json['type'] == 'radio') {
                $this->val = $this->json['values'];
            }
        }

        if (isset($this->json['name']) && $this->json['name'] != null)
            $this->name = $this->json['name'];

        if (isset($this->json['label']) && $this->json['label'] != null)
            $this->label = $this->json['label'];
    }

    public function jsonToHtml() {
        $echo = '<div class="form-group row"><label class="col-md-3 col-form-label" for="inputGroupSelect0'.self::$form_index.'">'.$this->label.'</label>';

        switch ($this->type) {
            case 'text':
                return $echo.'<div class="col-md-9"><input name="'.$this->name.'" class="form-control"></div></div>';
            break;

            case 'select':
                $echo .= '<select name="'.$this->name.'">';
                    $echo .= $this->valuesSelect();
                $echo .= '</select>';
                return $echo;
            break;

            case 'radio':
                $echo .= $this->valuesRadio();
                return $echo.'</div>';
            break;

            default:
                return null;
        }

    }

    public function valuesSelect() {
        $echo = '';
        foreach ($this->val as $item) {
            $echo .= '<option value="'.$item['value'].'">'.$item['display_value'].'</option>';
        }
        return $echo;
    }

    public function valuesRadio() {
        $echo = '<div class="col-md-9">';
        $i=0;
        foreach ($this->val as $item) {
            $echo .= '<div class="form-check form-check-inline">';
            $echo .= '  <input class="form-check-input" type="radio" name="'.$this->name.'" id="inlineRadio'.self::$form_index.$i.'" value="'.$item['value'].'" '.(($i == 0) ? 'checked':'').'>
                        <label class="form-check-label" for="inlineRadio'.self::$form_index.$i.'">'.$item['name'].'</label>';
            $echo .= '</div>';
            $i++;
        }
        $echo .= '</div>';

        return $echo;
    }
}
