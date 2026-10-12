import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index } from '@/routes/products';
import type { Product } from '@/types';

export default function Products({ products }: { products: Product[] }) {
    return (
        <>
            <Head title="Products" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Products" />

                    <Button asChild>
                        <Link href={create()}>New product</Link>
                    </Button>
                </div>

                <table className="w-full text-left text-sm">
                    <thead className="border-b text-muted-foreground">
                        <tr>
                            <th className="py-2">Name</th>
                            <th className="py-2">Price</th>
                            <th className="py-2" />
                        </tr>
                    </thead>
                    <tbody>
                        {products.map((product) => (
                            <tr key={product.id} className="border-b">
                                <td className="py-2">{product.name}</td>
                                <td className="py-2">{product.price}</td>
                                <td className="space-x-2 py-2 text-right">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(product.id)}>
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            router.delete(
                                                destroy(product.id).url,
                                            )
                                        }
                                    >
                                        Delete
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

Products.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: index(),
        },
    ],
};
