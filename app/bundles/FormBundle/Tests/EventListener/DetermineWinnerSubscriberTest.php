<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\EventListener\DetermineWinnerSubscriber;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Translation\TranslatorInterface;

class DetermineWinnerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $em;
    private $translator;

    /**
     * @var DetermineWinnerSubscriber
     */
    private $subscriber;

    protected function setup()
    {
        parent::setUp();

        $this->em         = $this->createMock(EntityManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->subscriber = new DetermineWinnerSubscriber($this->em, $this->translator);
    }

    public function testOnDetermineSubmissionWinner()
    {
        $parentMock    = $this->createMock(Page::class);
        $childMock     = $this->createMock(Page::class);
        $children      = [2 => $childMock];
        $repoMock      = $this->createMock(SubmissionRepository::class);
        $parameters    = ['parent' => $parentMock, 'children' => $children];
        $event         = new DetermineWinnerEvent($parameters);
        $startDate     = new \DateTime();

        $transSubmissions = 'submissions';
        $transHits        = 'hits';

        $counts = [
            1 => [
                'count' => 20,
                'id'    => 1,
                'name'  => 'Test 5',
                'total' => 100,
                ],
            2 => [
                'count' => 25,
                'id'    => 2,
                'name'  => 'Test 6',
                'total' => 150,
                ],
        ];

        $this->translator->expects($this->at(0))
            ->method('trans')
            ->willReturn($transSubmissions);

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->willReturn($transHits);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repoMock);

        $parentMock->expects($this->any())
            ->method('isPublished')
            ->willReturn(true);

        $childMock->expects($this->any())
            ->method('isPublished')
            ->willReturn(true);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $childMock->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $repoMock->expects($this->once())
            ->method('getSubmissionCountsByPage')
            ->with([1, 2], $startDate)
            ->willReturn($counts);

        $this->subscriber->onDetermineSubmissionWinner($event);

        $expectedData = [
            $transSubmissions => [$counts[1]['count'], $counts[2]['count']],
            $transHits        => [$counts[1]['total'], $counts[2]['total']],
         ];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [1]);
        $this->assertEquals($abTestResults['support']['data'], $expectedData);
    }
}
