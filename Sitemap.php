<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class Sitemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $frontUrl;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->frontUrl = URL::to(config('api.front_url'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemapIndex */
        $sitemapIndex = App::make('sitemap');

        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-pages.xml');
        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-categories.xml');
        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-products.xml');
        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-stores.xml');
        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-farmers.xml');
        $sitemapIndex->addSitemap($this->frontUrl . '/sitemap-blogs.xml');

        $sitemapIndex->store('sitemapindex');

        $this->pages();
        $this->categories();
        $this->products();
        $this->stores();
        $this->farmers();
        $this->blogs();
    }

    private function pages()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $sitemap->add($this->frontUrl);
        $sitemap->add($this->frontUrl . '/help');
        $sitemap->add($this->frontUrl . '/help/about');
        $sitemap->add($this->frontUrl . '/help/contacts');
        $sitemap->add($this->frontUrl . '/help/delivery-payment');

        // todo change
        $sitemap->add($this->frontUrl . '/vacancies');
        $sitemap->add($this->frontUrl . '/farmer-fund');

        $sitemap->store('xml', 'sitemap-pages');
    }

    private function categories()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $sitemap->add($this->frontUrl . '/catalog');
        $categories = Category::with('parent')->get();
        foreach ($categories as $node) {
            $sitemap->add(
                $this->frontUrl . '/catalog/' . $this->categoryPath($node),
                $node->updated_at,
                null,
                'daily'
            );
        }

        $sitemap->store('xml', 'sitemap-categories');
    }

    private function products()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $products = Product::with('categories')->get();
        foreach ($products as $product) {
            foreach ($product->categories as $category) {
                $sitemap->add(
                    $this->frontUrl . '/catalog/' . $this->categoryPath($category) . '/' . $product->slug,
                    $product->updated_at,
                    null,
                    'daily'
                );
            }
        }

        $sitemap->store('xml', 'sitemap-products');
    }

    private function stores()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $sitemap->add($this->frontUrl . '/shops');
        $stores = Store::all();
        foreach ($stores as $store) {
            $sitemap->add(
                $this->frontUrl . '/shops/' . $store->slug,
                $store->updated_at,
                null,
                'daily'
            );
        }
        $sitemap->store('xml', 'sitemap-stores');
    }

    private function farmers()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $sitemap->add($this->frontUrl . '/farmers');

        $farmers = Farmer::all();
        foreach ($farmers as $farmer) {
            $sitemap->add(
                $this->frontUrl . '/farmers/' . $farmer->slug,
                $farmer->updated_at,
                null,
                'daily'
            );
        }
        $sitemap->store('xml', 'sitemap-farmers');
    }

    private function blogs()
    {
        /** @var \Laravelium\Sitemap\Sitemap $sitemap */
        $sitemap = App::make('sitemap');

        $sitemap->add($this->frontUrl . '/blogs');

        $blogs = Blog::all();
        foreach ($blogs as $blog) {
            $sitemap->add(
                $this->frontUrl . '/blogs/' . $blog->slug,
                $blog->updated_at,
                null,
                'daily'
            );
        }

        $sitemap->store('xml', 'sitemap-blogs');
    }

    private function categoryPath($node)
    {
        $slugs = [
            $node->slug
        ];
        $currentNode = $node;
        while ($currentNode->parent) {
            $currentNode = $currentNode->parent;
            $slugs[] = $currentNode->slug;
        }

        return implode('/', array_reverse($slugs));
    }
}
