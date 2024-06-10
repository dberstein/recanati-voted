<?php declare(strict_types=1);

namespace Daniel\Vote\Model;

class Category
{
    const PARAM = 'category';
    public static array $categories = [
        'Cinema',
        'Dance',
        'Food',
        'Mobiles',
        'Music',
        'People',
        'Travel',
        'Work',
    ];

    protected $multiple = false;
    protected $name;
    protected array $selected = [];
    public function __construct($name, $multiple = null, $selected = null)
    {
        $this->name = (string) $name;
        $this->multiple = (bool) $multiple;
        $this->selected = $multiple ? (array) $selected : [$selected];
    }
    public function render(string $name): string
    {
        if ($this->multiple) {
            $name .= '[]';
        }
        $out = '';
        foreach (self::$categories as $category) {
            $checked = false;
            foreach ($this->selected as $s) {
                if ($s == $category) {
                    $checked = true;
                    break;
                }
            }
            $hCategory = htmlentities($category);
            $id = md5("{$this->name}:{$hCategory}");
            $out .= sprintf(
                '<input type="checkbox" id="%s" name="%s" value="%s"%s />',
                $id,
                $name,
                $hCategory,
                $checked ? ' checked' : ''
            );
            $out .= sprintf(
                '<label for="%s">%s</label>',
                $id,
                $hCategory
            );
        }
        return $out;
    }
}