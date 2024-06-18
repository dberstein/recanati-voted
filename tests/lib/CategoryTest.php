<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Daniel\Vote\Model\Category;

final class CategoryTest extends TestCase
{
    public function testRender(): void
    {
        $category = new Category('x');
        $this->assertEquals(
            $category->render('x'),
            '&nbsp;&nbsp;<input type="checkbox" id="e454205d0ca3bb8dab1041761a27ed21" name="x" value="Cinema" />&nbsp;<label class="form-check-label" for="e454205d0ca3bb8dab1041761a27ed21">Cinema</label>&nbsp;&nbsp;<input type="checkbox" id="d18271743d5956103b1f9f6baf3f1602" name="x" value="Dance" />&nbsp;<label class="form-check-label" for="d18271743d5956103b1f9f6baf3f1602">Dance</label>&nbsp;&nbsp;<input type="checkbox" id="2d9307b9f913b1986a07f66a2e057e85" name="x" value="Food" />&nbsp;<label class="form-check-label" for="2d9307b9f913b1986a07f66a2e057e85">Food</label>&nbsp;&nbsp;<input type="checkbox" id="238d73880f6834d29a28e137ff23deca" name="x" value="Mobiles" />&nbsp;<label class="form-check-label" for="238d73880f6834d29a28e137ff23deca">Mobiles</label>&nbsp;&nbsp;<input type="checkbox" id="121ab840194f7ff0a1c4ad2c63fe05f7" name="x" value="Music" />&nbsp;<label class="form-check-label" for="121ab840194f7ff0a1c4ad2c63fe05f7">Music</label>&nbsp;&nbsp;<input type="checkbox" id="4eb045d55c60c23b66fb60ba829786f0" name="x" value="People" />&nbsp;<label class="form-check-label" for="4eb045d55c60c23b66fb60ba829786f0">People</label>&nbsp;&nbsp;<input type="checkbox" id="b7404d1c4f1f6276eb37ca18a6b198ee" name="x" value="Travel" />&nbsp;<label class="form-check-label" for="b7404d1c4f1f6276eb37ca18a6b198ee">Travel</label>&nbsp;&nbsp;<input type="checkbox" id="e0049e1d9fc3ff87fa6e257ffde54766" name="x" value="Work" />&nbsp;<label class="form-check-label" for="e0049e1d9fc3ff87fa6e257ffde54766">Work</label>'
        );

        $category = new Category('x', true);
        $this->assertEquals(
            $category->render('x'),
            '&nbsp;&nbsp;<input type="checkbox" id="e454205d0ca3bb8dab1041761a27ed21" name="x[]" value="Cinema" />&nbsp;<label class="form-check-label" for="e454205d0ca3bb8dab1041761a27ed21">Cinema</label>&nbsp;&nbsp;<input type="checkbox" id="d18271743d5956103b1f9f6baf3f1602" name="x[]" value="Dance" />&nbsp;<label class="form-check-label" for="d18271743d5956103b1f9f6baf3f1602">Dance</label>&nbsp;&nbsp;<input type="checkbox" id="2d9307b9f913b1986a07f66a2e057e85" name="x[]" value="Food" />&nbsp;<label class="form-check-label" for="2d9307b9f913b1986a07f66a2e057e85">Food</label>&nbsp;&nbsp;<input type="checkbox" id="238d73880f6834d29a28e137ff23deca" name="x[]" value="Mobiles" />&nbsp;<label class="form-check-label" for="238d73880f6834d29a28e137ff23deca">Mobiles</label>&nbsp;&nbsp;<input type="checkbox" id="121ab840194f7ff0a1c4ad2c63fe05f7" name="x[]" value="Music" />&nbsp;<label class="form-check-label" for="121ab840194f7ff0a1c4ad2c63fe05f7">Music</label>&nbsp;&nbsp;<input type="checkbox" id="4eb045d55c60c23b66fb60ba829786f0" name="x[]" value="People" />&nbsp;<label class="form-check-label" for="4eb045d55c60c23b66fb60ba829786f0">People</label>&nbsp;&nbsp;<input type="checkbox" id="b7404d1c4f1f6276eb37ca18a6b198ee" name="x[]" value="Travel" />&nbsp;<label class="form-check-label" for="b7404d1c4f1f6276eb37ca18a6b198ee">Travel</label>&nbsp;&nbsp;<input type="checkbox" id="e0049e1d9fc3ff87fa6e257ffde54766" name="x[]" value="Work" />&nbsp;<label class="form-check-label" for="e0049e1d9fc3ff87fa6e257ffde54766">Work</label>'
        );

        $category = new Category('x', true, ['Work']);
        $this->assertEquals(
            $category->render('x'),
            '&nbsp;&nbsp;<input type="checkbox" id="e454205d0ca3bb8dab1041761a27ed21" name="x[]" value="Cinema" />&nbsp;<label class="form-check-label" for="e454205d0ca3bb8dab1041761a27ed21">Cinema</label>&nbsp;&nbsp;<input type="checkbox" id="d18271743d5956103b1f9f6baf3f1602" name="x[]" value="Dance" />&nbsp;<label class="form-check-label" for="d18271743d5956103b1f9f6baf3f1602">Dance</label>&nbsp;&nbsp;<input type="checkbox" id="2d9307b9f913b1986a07f66a2e057e85" name="x[]" value="Food" />&nbsp;<label class="form-check-label" for="2d9307b9f913b1986a07f66a2e057e85">Food</label>&nbsp;&nbsp;<input type="checkbox" id="238d73880f6834d29a28e137ff23deca" name="x[]" value="Mobiles" />&nbsp;<label class="form-check-label" for="238d73880f6834d29a28e137ff23deca">Mobiles</label>&nbsp;&nbsp;<input type="checkbox" id="121ab840194f7ff0a1c4ad2c63fe05f7" name="x[]" value="Music" />&nbsp;<label class="form-check-label" for="121ab840194f7ff0a1c4ad2c63fe05f7">Music</label>&nbsp;&nbsp;<input type="checkbox" id="4eb045d55c60c23b66fb60ba829786f0" name="x[]" value="People" />&nbsp;<label class="form-check-label" for="4eb045d55c60c23b66fb60ba829786f0">People</label>&nbsp;&nbsp;<input type="checkbox" id="b7404d1c4f1f6276eb37ca18a6b198ee" name="x[]" value="Travel" />&nbsp;<label class="form-check-label" for="b7404d1c4f1f6276eb37ca18a6b198ee">Travel</label>&nbsp;&nbsp;<input type="checkbox" id="e0049e1d9fc3ff87fa6e257ffde54766" name="x[]" value="Work" checked />&nbsp;<label class="form-check-label" for="e0049e1d9fc3ff87fa6e257ffde54766">Work</label>'
        );

        $category = new Category('x', true, ['Cinema', 'Work']);
        $this->assertEquals(
            $category->render('x'),
            '&nbsp;&nbsp;<input type="checkbox" id="e454205d0ca3bb8dab1041761a27ed21" name="x[]" value="Cinema" checked />&nbsp;<label class="form-check-label" for="e454205d0ca3bb8dab1041761a27ed21">Cinema</label>&nbsp;&nbsp;<input type="checkbox" id="d18271743d5956103b1f9f6baf3f1602" name="x[]" value="Dance" />&nbsp;<label class="form-check-label" for="d18271743d5956103b1f9f6baf3f1602">Dance</label>&nbsp;&nbsp;<input type="checkbox" id="2d9307b9f913b1986a07f66a2e057e85" name="x[]" value="Food" />&nbsp;<label class="form-check-label" for="2d9307b9f913b1986a07f66a2e057e85">Food</label>&nbsp;&nbsp;<input type="checkbox" id="238d73880f6834d29a28e137ff23deca" name="x[]" value="Mobiles" />&nbsp;<label class="form-check-label" for="238d73880f6834d29a28e137ff23deca">Mobiles</label>&nbsp;&nbsp;<input type="checkbox" id="121ab840194f7ff0a1c4ad2c63fe05f7" name="x[]" value="Music" />&nbsp;<label class="form-check-label" for="121ab840194f7ff0a1c4ad2c63fe05f7">Music</label>&nbsp;&nbsp;<input type="checkbox" id="4eb045d55c60c23b66fb60ba829786f0" name="x[]" value="People" />&nbsp;<label class="form-check-label" for="4eb045d55c60c23b66fb60ba829786f0">People</label>&nbsp;&nbsp;<input type="checkbox" id="b7404d1c4f1f6276eb37ca18a6b198ee" name="x[]" value="Travel" />&nbsp;<label class="form-check-label" for="b7404d1c4f1f6276eb37ca18a6b198ee">Travel</label>&nbsp;&nbsp;<input type="checkbox" id="e0049e1d9fc3ff87fa6e257ffde54766" name="x[]" value="Work" checked />&nbsp;<label class="form-check-label" for="e0049e1d9fc3ff87fa6e257ffde54766">Work</label>'
        );
    }
}
