<?php

namespace App\Controller;

use App\Dto\ProductInput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/product')]
final class ProductController extends AbstractController
{
    /**
     * The fields ?orderBy= accepts
     */
    private const array SORTABLE = ['id', 'name', 'price', 'createdAt', 'updatedAt'];

    /**
     * ?page= (from 0), ?size=, ?orderBy=name desc,id
     */
    #[Route('', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products): JsonResponse
    {
        $page = max(0, $request->query->getInt('page'));
        $size = min(100, max(1, $request->query->getInt('size', 20)));

        $order = [];
        foreach (explode(',', $request->query->getString('orderBy', 'id')) as $item) {
            [$field, $direction] = array_pad(preg_split('/\s+/', trim($item)), 2, 'asc');
            if (in_array($field, self::SORTABLE, true)) {
                $order[$field] = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            }
        }

        return $this->json(array_map($this->toArray(...), $products->findBy([], $order, $size, $page * $size)));
    }

    #[Route('/{id}', name: 'app_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($this->toArray($product));
    }

    #[Route('', name: 'app_product_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] ProductInput $input, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $this->fill(new Product(), $input);
        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json($this->toArray($product), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_product_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Product $product, #[MapRequestPayload] ProductInput $input, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->fill($product, $input);
        $entityManager->flush();

        return $this->json($this->toArray($product));
    }

    /**
     * Soft delete: the SQL filter hides the product from then on
     */
    #[Route('/{id}', name: 'app_product_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): Response
    {
        $product->setDeletedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function fill(Product $product, ProductInput $input): Product
    {
        return $product->setName($input->name)->setPrice((string) $input->price);
    }

    /**
     * The product as Gluo's API writes it, the contract the SPA reads
     */
    private function toArray(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => (float) $product->getPrice(),
            'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'deletedAt' => $product->getDeletedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
