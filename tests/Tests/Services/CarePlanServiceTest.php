<?php

/**
 * CarePlanServiceTest.php
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Tests\Services;

use OpenEMR\Services\CarePlanService;
use OpenEMR\Services\EncounterService;
use OpenEMR\Tests\Fixtures\CarePlanFixtureManager;
use OpenEMR\Tests\Fixtures\FormFixtureManager;
use PHPUnit\Framework\TestCase;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Services\PractitionerService;
use OpenEMR\Tests\Fixtures\PractitionerFixtureManager;

/**
 * Practitioner Service Tests
 * @coversDefaultClass OpenEMR\Services\PractitionerService
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Yash Bothra <yashrajbothra786gmail.com>
 * @copyright Copyright (c) 2020 Yash Bothra <yashrajbothra786gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class CarePlanServiceTest extends TestCase
{
    /**
     * @var CarePlanService
     */
    private $service;

    /**
     * @var CarePlanFixtureManager
     */
    private $fixtureManager;

    protected function setUp(): void
    {
        $this->service = new CarePlanService();
        $this->fixtureManager = new CarePlanFixtureManager();
        $this->fixture = (array) $this->fixtureManager->getSingleFixture();
    }

    protected function tearDown(): void
    {
        $this->fixtureManager->removeFixtures();
    }

    /**
     * @cover ::getOne
     */
    public function testGetOne()
    {
<<<<<<< HEAD
        $this->fixtureManager->installFixtures();

        // attempt to verify the uuid surrogate key is working correctly
        $expectedResult = sqlQuery("SELECT `fcp`.`id` AS `form_id`,`fe`.`uuid` AS `euuid`, `fcp`.`encounter` FROM `form_care_plan` fcp "
        . " JOIN `form_encounter` fe ON `fcp`.`encounter` = `fe`.`encounter` LIMIT 1");
        $expectedResult['euuid'] = UuidRegistry::uuidToString($expectedResult['euuid']);
        $uuid = $this->service->getSurrogateKeyForRecord($expectedResult);

        // getOne
        $actualResult = $this->service->getOne($uuid);
        $resultData = $actualResult->getData()[0];
        $this->assertNotNull($resultData);
        foreach (['euuid','form_id'] as $check) {
            $this->assertEquals($expectedResult[$check], $resultData[$check]);
        }
=======
        $this->markTestIncomplete("This test is not implemented");
    }

    /**
     * @cover ::getSurrogateKeyForRecord
     */
    public function testGetSurrogateKeyForRecord()
    {
        // we are going to use the old care plan
        $expectedResult = sqlQuery("SELECT `fcp`.`id` AS `form_id`,`fe`.`uuid` AS `euuid`, `fcp`.`encounter` FROM `form_care_plan` fcp "
        . " JOIN `form_encounter` fe ON `fcp`.`encounter` = `fe`.`encounter` LIMIT 1");

        $euuid = "960aaed3-de07-44a5-9328-835ebc822169";
        $expectedResult = ['euuid' => $euuid, 'form_id' => 1];

        $result1 = $expectedResult; // clones
        $result1['creation_timestamp'] = CarePlanService::V2_TIMESTAMP;
        $uuid = $this->service->getSurrogateKeyForRecord($result1);
        $this->assertStringContainsString(CarePlanService::SURROGATE_KEY_SEPARATOR_V1, $uuid, "v1 separator should be in surrogate key");

        // past our v2 date
        $result2 = $expectedResult;
        $result2['creation_timestamp'] = CarePlanService::V2_TIMESTAMP + 1;
        $uuid = $this->service->getSurrogateKeyForRecord($result2);
        $this->assertStringContainsString(CarePlanService::SURROGATE_KEY_SEPARATOR_V2, $uuid, "v2 separator should be in surrogate key");
>>>>>>> 56c81eaa9c98a4cb7e9fdeabc139d13e19aa916e
    }
}
