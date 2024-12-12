<?php

namespace RedBeanPHP;

use RedBeanPHP\OODBBean as OODBBean;

/**
 * SimpleModel
 * Base Model For All RedBeanPHP Models using FUSE.
 *
 * RedBeanPHP FUSE is a mechanism to connect beans to posthoc
 * models. Models are connected to beans by naming conventions.
 * Actions on beans will result in actions on models.
 *
 * @file       RedBeanPHP/SimpleModel.php
 * @author     Gabor de Mooij and the RedBeanPHP Team
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleModel implements SimpleModelInterface
{
    use SimpleModelTrait;
}
