<?php declare(strict_types=1);
/*.
    require_module 'standard';
.*/

/**
 *  @OA\Get(
 *      tags={"events"},
 *      path="/event/{id}",
 *      summary="Gets an event",
 *      @OA\Parameter(
 *          description="Id of the event",
 *          in="path",
 *          name="id",
 *          required=true,
 *          @OA\Schema(type="integer")
 *      ),
 *      @OA\Parameter(
 *          description="Include the resource instead of the ID.",
 *          in="query",
 *          name="include",
 *          required=false,
 *          explode=false,
 *          style="form",
 *          @OA\Schema(
 *              type="array",
 *              @OA\Items(
 *                  type="string",
 *                  enum={"cycle"}
 *              )
 *           )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Event found",
 *          @OA\JsonContent(
 *           ref="#/components/schemas/event"
 *          ),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          ref="#/components/responses/401"
 *      ),
 *      @OA\Response(
 *          response=404,
 *          ref="#/components/responses/event_not_found"
 *      ),
 *      security={{"ciab_auth":{}}}
 *  )
 **/

namespace App\Controller\Event;

use Slim\Http\Request;
use Slim\Http\Response;

use App\Controller\NotFoundException;

class GetEvent extends BaseEvent
{


    public function buildResource(Request $request, Response $response, $params): array
    {
        $event = $this->getEvent($params['id']);
        $this->id = $event['id'];
        return [
        \App\Controller\BaseController::RESOURCE_TYPE,
        $event
        ];

    }


    /* end GetEvent */
}
