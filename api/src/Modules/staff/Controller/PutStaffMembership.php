<?php declare(strict_types=1);
/*.
    require_module 'standard';
.*/

/**
 *  @OA\Put(
 *      tags={"members"},
 *      path="/member/{id}/staff_membership",
 *      summary="Update a staff membership for the member",
 *      @OA\Parameter(
 *          description="The ID of the member",
 *          in="path",
 *          name="id",
 *          required=true,
 *          @OA\Schema(
 *              description="Member ID",
 *              type="integer"
 *          )
 *      ),
 *      @OA\RequestBody(
 *          @OA\MediaType(
 *              mediaType="multipart/form-data",
 *              @OA\Schema(
 *                  @OA\Property(
 *                      property="Department",
 *                      description="ID or name of the department",
 *                      nullable=false,
 *                      type="string"
 *                  ),
 *                  @OA\Property(
 *                      property="Position",
 *                      description="ID of the position",
 *                      nullable=false,
 *                      type="integer"
 *                  ),
 *                  @OA\Property(
 *                      property="Note",
 *                      description="Notes about member",
 *                      nullable=true,
 *                      type="string"
 *                  )
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="OK"
 *      ),
 *      @OA\Response(
 *          response=400,
 *          ref="#/components/responses/400"
 *      ),
 *      @OA\Response(
 *          response=401,
 *          ref="#/components/responses/401"
 *      ),
 *      @OA\Response(
 *          response=404,
 *          ref="#/components/responses/department_not_found"
 *      ),
 *      security={{"ciab_auth":{}}}
 *  )
 */


namespace App\Modules\staff\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Error\InvalidParameterException;
use Atlas\Query\Update;

class PutStaffMembership extends BaseStaff
{


    public function buildResource(Request $request, Response $response, $params): array
    {
        $body = $request->getParsedBody();
        if (!$body) {
            throw new InvalidParameterException('Body required');
        }

        $required = ['Department', 'Position'];
        $this->checkRequiredBody($request, $required);

        $department = $this->getDepartment($body['Department']);
        $departmentId = $department['id'];
        $position = $body['Position'];
        $permissions = ['api.put.staff.'.$departmentId.'.'.$position, 'api.put.staff.all'];
        $this->checkPermissions($permissions);

        $update = Update::new($this->container->db)
        ->table('ConComList')
        ->column('PositionID', $body['Position']);

        if (array_key_exists('Note', $body)) {
            $note = $body['Note'];
            $update->column('Note', $note);
        }

        $update->WhereEquals(['AccountID' => $params['id'], 'DepartmentID' => $department['id']]);
        $update->perform();

        return [null];

    }

    
    /* end PutStaffMembership */
}
