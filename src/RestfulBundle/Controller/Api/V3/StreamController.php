<?php
namespace RestfulBundle\Controller\Api\V3;

use CoreBundle\Controller\BaseController;
use CoreBundle\Repository\StreamRepository;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 * @Route("/api/v3/")
 */
class StreamController  extends BaseController
{
    /**
     * @Route("streams",
     *     options = { "expose" = true },
     *     name="get_active_streams")
     * @Method({"GET"})
     */
    public function getActiveRecordingsAction(Request $request)
    {
        /** @var StreamRepository $streamRepository */
        $streamRepository =  $this->get('core.stream_repository');
        $streams =  $streamRepository->findAll();
    }


}