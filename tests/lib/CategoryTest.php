<?php declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Daniel\Vote\Model\Category;

final class CategoryTest extends TestCase {
    public function testRender() {
        $category = new Category();
        $this->assertEquals(
            $category->render('x'),
            '<input type="checkbox" id="Cinema" name="x" value="Cinema" /><label for="Cinema">Cinema</label><input type="checkbox" id="Dance" name="x" value="Dance" /><label for="Dance">Dance</label><input type="checkbox" id="Food" name="x" value="Food" /><label for="Food">Food</label><input type="checkbox" id="Mobiles" name="x" value="Mobiles" /><label for="Mobiles">Mobiles</label><input type="checkbox" id="Music" name="x" value="Music" /><label for="Music">Music</label><input type="checkbox" id="People" name="x" value="People" /><label for="People">People</label><input type="checkbox" id="Travel" name="x" value="Travel" /><label for="Travel">Travel</label><input type="checkbox" id="Work" name="x" value="Work" /><label for="Work">Work</label>'
        );

        $category = new Category(true);
        $this->assertEquals(
            $category->render('x'),
            '<input type="checkbox" id="Cinema" name="x[]" value="Cinema" /><label for="Cinema">Cinema</label><input type="checkbox" id="Dance" name="x[]" value="Dance" /><label for="Dance">Dance</label><input type="checkbox" id="Food" name="x[]" value="Food" /><label for="Food">Food</label><input type="checkbox" id="Mobiles" name="x[]" value="Mobiles" /><label for="Mobiles">Mobiles</label><input type="checkbox" id="Music" name="x[]" value="Music" /><label for="Music">Music</label><input type="checkbox" id="People" name="x[]" value="People" /><label for="People">People</label><input type="checkbox" id="Travel" name="x[]" value="Travel" /><label for="Travel">Travel</label><input type="checkbox" id="Work" name="x[]" value="Work" /><label for="Work">Work</label>'
        );

        $category = new Category(true, ['Work']);
        $this->assertEquals(
            $category->render('x'),
            '<input type="checkbox" id="Cinema" name="x[]" value="Cinema" /><label for="Cinema">Cinema</label><input type="checkbox" id="Dance" name="x[]" value="Dance" /><label for="Dance">Dance</label><input type="checkbox" id="Food" name="x[]" value="Food" /><label for="Food">Food</label><input type="checkbox" id="Mobiles" name="x[]" value="Mobiles" /><label for="Mobiles">Mobiles</label><input type="checkbox" id="Music" name="x[]" value="Music" /><label for="Music">Music</label><input type="checkbox" id="People" name="x[]" value="People" /><label for="People">People</label><input type="checkbox" id="Travel" name="x[]" value="Travel" /><label for="Travel">Travel</label><input type="checkbox" id="Work" name="x[]" value="Work" checked /><label for="Work">Work</label>'
        );

        $category = new Category(true, ['Cinema', 'Work']);
        $this->assertEquals(
            $category->render('x'),
            '<input type="checkbox" id="Cinema" name="x[]" value="Cinema" checked /><label for="Cinema">Cinema</label><input type="checkbox" id="Dance" name="x[]" value="Dance" /><label for="Dance">Dance</label><input type="checkbox" id="Food" name="x[]" value="Food" /><label for="Food">Food</label><input type="checkbox" id="Mobiles" name="x[]" value="Mobiles" /><label for="Mobiles">Mobiles</label><input type="checkbox" id="Music" name="x[]" value="Music" /><label for="Music">Music</label><input type="checkbox" id="People" name="x[]" value="People" /><label for="People">People</label><input type="checkbox" id="Travel" name="x[]" value="Travel" /><label for="Travel">Travel</label><input type="checkbox" id="Work" name="x[]" value="Work" checked /><label for="Work">Work</label>'
        );
    }
}