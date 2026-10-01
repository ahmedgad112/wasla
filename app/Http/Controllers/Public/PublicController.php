<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryDriver;
use App\Models\MenuItem;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\LandingCmsService;
use App\Services\PublicCatalogCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function __construct(protected LandingCmsService $cmsService) {}

    public function home(): Response
    {
        $activeOffers = Cache::remember(PublicCatalogCache::ACTIVE_OFFERS, 60, function () {
            return Offer::with(['restaurant:id,name,slug,logo'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->latest()
                ->take(12)
                ->get()
                ->toArray();
        });

        $restaurants = Cache::remember(PublicCatalogCache::FEATURED_RESTAURANTS, 60, function () {
            return Restaurant::query()
                ->whereIn('status', ['ACTIVE', 'SUSPENDED'])
                ->select([
                    'id', 'name', 'slug', 'logo', 'cover_image', 'description',
                    'phone', 'address', 'delivery_fee', 'estimated_delivery_time',
                    'minimum_order_amount', 'student_discount_percentage',
                    'opening_time', 'closing_time', 'status', 'availability_status',
                    'delivery_provider', 'delivery_enabled',
                ])
                ->withCount(['menuItems' => fn ($q) => $q->where('is_available', true)])
                ->withCount(['offers' => fn ($q) => $q->where('is_active', true)])
                ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")
                ->latest()
                ->get()
                ->toArray();
        });

        $stats = Cache::remember(PublicCatalogCache::STATS, 120, function () {
            $totalDishes = MenuItem::where('is_available', true)->count();

            return [
                'restaurants' => Restaurant::where('status', 'ACTIVE')->count(),
                'orders' => Order::where('status', 'DELIVERED')->count(),
                'drivers' => DeliveryDriver::where('is_active', true)->count(),
                'customers' => Customer::count(),
                'totalDishes' => $totalDishes,
            ];
        });

        $leaderboard = Cache::remember(PublicCatalogCache::HOME_LEADERBOARD, 300, function () use ($stats) {
            $totalDishes = $stats['totalDishes'];

            return [
                'rankings' => [
                    [
                        'userId' => 1,
                        'userName' => 'زياد طارق (طالب تكنولوجية برج العرب)',
                        'badge' => 'ملك الفطار 👑',
                        'totalOrders' => 42,
                        'totalItems' => 118,
                    ],
                    [
                        'userId' => 2,
                        'userName' => 'مصطفى حسني (فريق إدارة التقديمات)',
                        'badge' => 'عاشق السندوتشات 🥪',
                        'totalOrders' => 35,
                        'totalItems' => 94,
                    ],
                    [
                        'userId' => 3,
                        'userName' => 'محمد عادل (كلية تكنولوجيا الصناعة)',
                        'badge' => 'عميد الفطار 🥇',
                        'totalOrders' => 28,
                        'totalItems' => 76,
                    ],
                ],
                'kingOfBreakfast' => [
                    'userId' => 1,
                    'userName' => 'زياد طارق (طالب تكنولوجية برج العرب)',
                    'badge' => 'ملك الفطار 👑',
                    'totalOrders' => 42,
                    'totalItems' => 118,
                ],
                'totalOrdersInSystem' => max(144, Order::count()),
                'totalItemsInSystem' => max(384, $totalDishes * 4),
            ];
        });

        return Inertia::render('Public/Home', [
            'restaurants' => $restaurants,
            'featuredRestaurants' => $restaurants,
            'totalDishes' => $stats['totalDishes'],
            'activeOffers' => $activeOffers,
            'leaderboard' => $leaderboard,
            'cms' => $this->cmsService->getPublicSettings(),
            'stats' => $stats,
        ]);
    }

    public function leaderboard(): Response
    {
        $stats = Cache::remember(PublicCatalogCache::STATS, 120, function () {
            $totalDishes = MenuItem::where('is_available', true)->count();

            return [
                'restaurants' => Restaurant::where('status', 'ACTIVE')->count(),
                'orders' => Order::where('status', 'DELIVERED')->count(),
                'drivers' => DeliveryDriver::where('is_active', true)->count(),
                'customers' => Customer::count(),
                'totalDishes' => $totalDishes,
            ];
        });

        $leaderboard = [
            'rankings' => [
                [
                    'userId' => 1,
                    'userName' => 'زياد طارق (طالب تكنولوجية برج العرب)',
                    'badge' => 'ملك الفطار 👑',
                    'totalOrders' => 42,
                    'totalItems' => 118,
                ],
                [
                    'userId' => 2,
                    'userName' => 'مصطفى حسني (فريق إدارة التقديمات)',
                    'badge' => 'عاشق السندوتشات 🥪',
                    'totalOrders' => 35,
                    'totalItems' => 94,
                ],
                [
                    'userId' => 3,
                    'userName' => 'محمد عادل (كلية تكنولوجيا الصناعة)',
                    'badge' => 'عميد الفطار 🥇',
                    'totalOrders' => 28,
                    'totalItems' => 76,
                ],
                [
                    'userId' => 4,
                    'userName' => 'أحمد محمود (طالب هندسة)',
                    'badge' => 'صديق المنيو 🌟',
                    'totalOrders' => 21,
                    'totalItems' => 52,
                ],
                [
                    'userId' => 5,
                    'userName' => 'سارة إبراهيم (إدارة التقديمات)',
                    'badge' => 'نجمة الصباح ☕',
                    'totalOrders' => 18,
                    'totalItems' => 44,
                ],
            ],
            'kingOfBreakfast' => [
                'userId' => 1,
                'userName' => 'زياد طارق (طالب تكنولوجية برج العرب)',
                'badge' => 'ملك الفطار 👑',
                'totalOrders' => 42,
                'totalItems' => 118,
            ],
            'totalOrdersInSystem' => max(144, Order::count()),
            'totalItemsInSystem' => max(384, $stats['totalDishes'] * 4),
        ];

        return Inertia::render('Public/Leaderboard', [
            'leaderboard' => $leaderboard,
        ]);
    }

    public function restaurants(): Response
    {
        $restaurants = Restaurant::whereIn('status', ['ACTIVE', 'SUSPENDED'])
            ->select([
                'id', 'name', 'slug', 'logo', 'cover_image', 'description',
                'delivery_fee', 'estimated_delivery_time', 'minimum_order_amount',
                'opening_time', 'closing_time', 'address', 'status', 'availability_status',
                'student_discount_percentage', 'phone', 'delivery_provider', 'delivery_enabled',
            ])
            ->withCount(['offers' => fn ($q) => $q->where('is_active', true)])
            ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(12);

        return Inertia::render('Public/Restaurants', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Lightweight poll endpoint so customers see open/busy/closed changes quickly.
     */
    public function availabilityStatuses(): JsonResponse
    {
        $restaurants = Cache::remember(PublicCatalogCache::AVAILABILITY, 5, function () {
            return Restaurant::query()
                ->whereIn('status', ['ACTIVE', 'SUSPENDED'])
                ->orderBy('id')
                ->get(['id', 'status', 'availability_status'])
                ->toArray();
        });

        return response()
            ->json(['restaurants' => $restaurants])
            ->header('Cache-Control', 'public, max-age=5');
    }

    public function restaurantDetails(string $slug): Response|RedirectResponse
    {
        $cacheKey = PublicCatalogCache::restaurantKey($slug);
        $restaurant = Cache::get($cacheKey);

        if (! is_array($restaurant)) {
            $model = Restaurant::where('slug', $slug)
                ->whereIn('status', ['ACTIVE', 'SUSPENDED'])
                ->with([
                    'categories' => function ($q) {
                        $q->where('is_active', true)
                            ->orderBy('sort_order')
                            ->select(['id', 'restaurant_id', 'name', 'slug', 'image', 'sort_order', 'is_active'])
                            ->with(['menuItems' => function ($q) {
                                $q->where('is_available', true)
                                    ->orderBy('sort_order')
                                    ->select([
                                        'id', 'restaurant_id', 'category_id', 'name', 'description',
                                        'price', 'discount_price', 'image', 'is_available',
                                        'is_featured', 'preparation_time', 'sort_order',
                                    ])
                                    ->with([
                                        'options:id,menu_item_id,name,is_required',
                                        'options.values:id,menu_item_option_id,name,price',
                                        'addons:id,menu_item_id,name,price,is_available',
                                    ]);
                            }]);
                    },
                    'offers' => function ($q) {
                        $q->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                            });
                    },
                ])
                ->first();

            if (! $model) {
                return redirect()
                    ->route('restaurants')
                    ->with('error', 'هذا المطعم غير متاح أو تم حذفه.');
            }

            $restaurant = $model->toArray();
            Cache::put($cacheKey, $restaurant, 30);
        }

        return Inertia::render('Public/RestaurantDetails', [
            'restaurant' => $restaurant,
        ]);
    }

    public function offers(): Response
    {
        $offers = Offer::with(['restaurant:id,name,slug,logo'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latest()
            ->paginate(16);

        return Inertia::render('Public/Offers', [
            'offers' => $offers,
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/Contact');
    }
}
