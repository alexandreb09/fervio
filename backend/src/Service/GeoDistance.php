<?php

namespace App\Service;

class GeoDistance
{
    private const EARTH_RADIUS_KM = 6371.0;
    private const KM_PER_DEGREE_LAT = 111.0;

    /** Distance à vol d'oiseau entre deux points (formule de Haversine), en km. */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Boîte englobante approximative autour d'un point pour un rayon donné (en km).
     * Sert de pré-filtre SQL bon marché ; le filtrage exact se fait ensuite via distanceKm().
     *
     * @return array{latMin: float, latMax: float, lonMin: float, lonMax: float}
     */
    public static function boundingBox(float $lat, float $lon, float $radiusKm): array
    {
        $latDelta = $radiusKm / self::KM_PER_DEGREE_LAT;
        $kmPerDegreeLon = self::KM_PER_DEGREE_LAT * max(cos(deg2rad($lat)), 0.01);
        $lonDelta = $radiusKm / $kmPerDegreeLon;

        return [
            'latMin' => $lat - $latDelta,
            'latMax' => $lat + $latDelta,
            'lonMin' => $lon - $lonDelta,
            'lonMax' => $lon + $lonDelta,
        ];
    }

    /**
     * Ne garde que les entités (ayant getLatitude()/getLongitude()) situées à moins de
     * $radiusKm du point donné, triées par distance croissante.
     *
     * @template T of object
     * @param T[] $entities
     * @return T[]
     */
    public static function filterAndSortByDistance(array $entities, float $lat, float $lon, int $radiusKm): array
    {
        $withDistance = [];
        foreach ($entities as $entity) {
            $distance = self::distanceKm($lat, $lon, (float) $entity->getLatitude(), (float) $entity->getLongitude());
            if ($distance <= $radiusKm) {
                $withDistance[] = [$entity, $distance];
            }
        }
        usort($withDistance, fn($a, $b) => $a[1] <=> $b[1]);

        return array_map(fn($pair) => $pair[0], $withDistance);
    }
}
