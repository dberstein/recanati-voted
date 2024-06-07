<?php declare(strict_types=1);

namespace Daniel\Vote\Model;

class Category {
    public static array $categories = [
        'Mobiles',
        'Food',
        'Cinema',
    ];

    protected $multiple = false;
    protected array $selected = [];
    public function __construct($multiple = null, $selected = null) {
        $this->multiple = (bool) $multiple;
        $this->selected = $multiple ? (array) $selected : [$selected];
    }
    public function render(string $name): string {
        if ($this->multiple) {
            $name .= '[]';
        }
        $out = '<select name="'.$name.'" multiple>';

        foreach (self::$categories as $category) {
            $selected = false;
            foreach ($this->selected as $s) {
                if ($s == $category) {
                    $selected = true;
                    break;
                }
            }
            $out .= sprintf('<option%s>%s</option>', $selected ? ' selected' : '', htmlentities($category));
        }
        $out .= '</select>';
        return $out;
    }
}