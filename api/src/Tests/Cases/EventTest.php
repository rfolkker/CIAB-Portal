<?php

namespace App\Tests\TestCase\Controller;

use App\Tests\Base\CiabTestCase;

class EventTest extends CiabTestCase
{

    private $events = array();

    private $cycle = null;


    protected function setUp(): void
    {
        parent::setUp();
        $when = date('Y-m-d', strtotime('+1998 years'));
        $to = date('Y-m-d', strtotime('+2002 years'));
        $this->cycle = $this->runSuccessJsonRequest('POST', '/cycle', null, ['date_from' => $when, 'date_to' => $to], 201);
        $when = date('Y-m-d', strtotime('+1999 years'));
        $to = date('Y-m-d', strtotime('+1999 years'));
        $this->events[] = $this->runSuccessJsonRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => $to, 'name' => 'PHPTest-a-con'], 201);

    }


    protected function tearDown(): void
    {
        foreach ($this->events as $event) {
            $this->runRequest('DELETE', '/event/'.$event->id, null, null, 204);
        }
        if ($this->cycle !== null) {
            $this->runRequest('DELETE', '/cycle/'.$this->cycle->id, null, null, 204);
        }
        parent::tearDown();

    }


    public function testEvent(): void
    {
        $when = date('Y-m-d', strtotime('+1999 years'));
        $to = date('Y-m-d', strtotime('+1999 years'));

        $data = $this->runSuccessJsonRequest('GET', '/event');
        $this->assertSame($data->type, 'event_list');

        $data = $this->runSuccessJsonRequest('GET', '/event/current');
        $this->assertSame($data->type, 'event');

        $this->runSuccessJsonRequest(
            'GET',
            '/event',
            ['begin' => $when]
        );

        $this->runSuccessJsonRequest(
            'GET',
            '/event',
            ['end' => $to]
        );

        $data = $this->runSuccessJsonRequest(
            'GET',
            '/event',
            ['end' => $to,'begin' => $when]
        );
        $id = $data->data[0]->id;
        $data = $this->runSuccessJsonRequest('GET', '/event/'.$id);
        $this->assertIncludes($data, 'cycle');

    }


    public function testPostEvent(): void
    {
        $when = date('Y-m-d', strtotime('+2000 years'));
        $to = date('Y-m-d', strtotime('+2001 years'));
        $this->runRequest('POST', '/event', null, null, 400);
        $this->runRequest('POST', '/event', null, ['date_from' => $when], 400);
        $this->runRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => 'notadate'], 400);
        $this->runRequest('POST', '/event', null, ['date_from' => 'notadate', 'date_to' => $to], 400);
        $this->runRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => $to], 400);
        $this->runRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => $to], 400);
        $when = date('Y-m-d', strtotime('+3000 years'));
        $to = date('Y-m-d', strtotime('+3001 years'));
        $this->runRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => $to, 'name' => 'PHPTest-a-con'], 400);
        $when = date('Y-m-d', strtotime('+2000 years'));
        $to = date('Y-m-d', strtotime('+2001 years'));

        $this->events[] = $this->runSuccessJsonRequest('POST', '/event', null, ['date_from' => $when, 'date_to' => $to, 'name' => 'NEW PHPTest-a-con'], 201);

    }


    public function testEventErrors(): void
    {
        $this->runRequest(
            'GET',
            '/event',
            ['begin' => 'not-a-date'],
            null,
            400
        );

        $this->runRequest(
            'GET',
            '/event',
            ['end' => 'not-a-date'],
            null,
            400
        );

        $this->runRequest('GET', '/event/-1', null, null, 404);

    }


    /* End */
}
