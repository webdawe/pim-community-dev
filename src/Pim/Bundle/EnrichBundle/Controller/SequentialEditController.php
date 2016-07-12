<?php

namespace Pim\Bundle\EnrichBundle\Controller;

use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Pim\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Pim\Bundle\EnrichBundle\Factory\SequentialEditFactory;
use Pim\Bundle\UserBundle\Context\UserContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Sequential edit action controller for products
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SequentialEditController
{
    /** @var RouterInterface */
    protected $router;

    /** @var MassActionDispatcher */
    protected $massActionDispatcher;

    /** @var ObjectRepository */
    protected $seqEditRepository;

    /** @var SequentialEditFactory */
    protected $seqEditFactory;

    /** @var SaverInterface */
    protected $seqEditSaver;

    /** @var UserContext */
    protected $userContext;

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var array */
    protected $objects;

    /**
     * @param RouterInterface       $router
     * @param MassActionDispatcher  $massActionDispatcher
     * @param ObjectRepository      $seqEditRepository
     * @param SequentialEditFactory $seqEditFactory
     * @param SaverInterface        $seqEditSaver
     * @param UserContext           $userContext
     * @param NormalizerInterface   $normalizer
     */
    public function __construct(
        RouterInterface $router,
        MassActionDispatcher $massActionDispatcher,
        ObjectRepository $seqEditRepository,
        SequentialEditFactory $seqEditFactory,
        SaverInterface $seqEditSaver,
        UserContext $userContext,
        NormalizerInterface $normalizer
    ) {
        $this->router               = $router;
        $this->massActionDispatcher = $massActionDispatcher;
        $this->seqEditRepository    = $seqEditRepository;
        $this->seqEditFactory       = $seqEditFactory;
        $this->seqEditSaver         = $seqEditSaver;
        $this->userContext          = $userContext;
        $this->normalizer           = $normalizer;
    }

    /**
     * Action for product sequential edition
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function sequentialEditAction(Request $request)
    {
        if ($this->seqEditRepository->findBy(['user' => $this->userContext->getUser()])) {
            return new RedirectResponse(
                $this->router->generate(
                    'pim_enrich_product_index',
                    ['dataLocale' => $request->get('dataLocale')]
                )
            );
        }

        $sequentialEdit = $this->seqEditFactory->create(
            $this->massActionDispatcher->dispatch($request),
            $this->userContext->getUser()
        );

        $this->seqEditSaver->save($sequentialEdit);

        return new RedirectResponse(
            $this->router->generate(
                'pim_enrich_product_edit',
                [
                    'dataLocale' => $request->get('dataLocale'),
                    'id'         => current($sequentialEdit->getObjectSet())
                ]
            )
        );
    }

    /**
     * @return JsonResponse
     */
    public function getAction()
    {
        $sequentialEdit = $this->seqEditRepository->findBy([
            'user' => $this->userContext->getUser()
        ]);

        return new JsonResponse($this->normalizer->normalize($sequentialEdit, 'internal_api'));
    }
}
