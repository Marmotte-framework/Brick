<?php
/**
 * MIT License
 *
 * Copyright (c) 2023-Present Kevin Traini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Marmotte\Brick\Bricks;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\InstalledVersions;
use Marmotte\Brick\Brick;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Exceptions\PackageContainsNoBrickException;
use Marmotte\Brick\Exceptions\PackageContainsSeveralBrickException;
use ReflectionClass;
use ReflectionException;

/**
 * This class is used to load all Bricks
 */
final class BrickLoader
{
    private const PACKAGE_TYPE = 'marmotte-brick';
    private const CACHE_DIR    = 'bricks';

    /**
     * Load all installed Bricks
     *
     * @throws PackageContainsSeveralBrickException
     * @throws PackageContainsNoBrickException
     */
    public function loadBricks(): void
    {
        $packages = InstalledVersions::getInstalledPackagesByType(self::PACKAGE_TYPE);

        foreach ($packages as $package) {
            try {
                $brick = $this->loadPackageBrick($package);
                if ($brick) {
                    BrickManager::instance()->addBrick($brick);
                }
            } catch (ReflectionException) {
                // Ignore ReflectionException, but let pass others
            }
        }

        CacheManager::instance()->save(self::CACHE_DIR, BrickLoader::class, BrickManager::instance()->getBricks());
    }

    /**
     * Load one Brick located in $dir
     *
     * @throws PackageContainsSeveralBrickException
     * @throws PackageContainsNoBrickException
     */
    public function loadFromDir(string $dir, string $package = ''): void
    {
        try {
            BrickManager::instance()->addBrick($this->loadDirBrick($dir, $package));
        } catch (ReflectionException) {
            // Ignore ReflectionException, but let pass others
        }

        CacheManager::instance()->save(self::CACHE_DIR, BrickLoader::class, BrickManager::instance()->getBricks());
    }

    /**
     * Load Bricks stored in cache file
     *
     * @throws PackageContainsSeveralBrickException
     * @throws PackageContainsNoBrickException
     */
    public function loadFromCache(): void
    {
        if (!CacheManager::instance()->exists(self::CACHE_DIR, BrickLoader::class)) {
            $this->loadBricks();
            return;
        }

        /** @var BrickPresenter[] */
        $bricks = CacheManager::instance()->load(self::CACHE_DIR, BrickLoader::class);

        BrickManager::instance()->addBricks(...$bricks);
    }

    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

    /**
     * @throws PackageContainsNoBrickException
     * @throws PackageContainsSeveralBrickException
     * @throws ReflectionException
     */
    private function loadPackageBrick(string $package): ?BrickPresenter
    {
        $install_path = InstalledVersions::getInstallPath($package);
        if ($install_path === null) {
            return null; // Package is not installed, ignore it
        }

        return $this->loadDirBrick($install_path, $package);
    }

    /**
     * @throws PackageContainsNoBrickException
     * @throws PackageContainsSeveralBrickException
     * @throws ReflectionException
     */
    private function loadDirBrick(string $dir, string $package): BrickPresenter
    {
        $map = ClassMapGenerator::createMap($dir);

        /** @var ReflectionClass<Brick>|null $brick */
        $brick = null;
        /** @var ReflectionClass[] $class_map */
        $class_map = [];

        foreach ($map as $symbol => $_path) { // For each class in Brick package
            $ref = new ReflectionClass($symbol);

            if ($ref->isSubclassOf(Brick::class)) { // If class is Brick
                if ($brick === null) {
                    $brick = $ref;
                    continue;
                } else {
                    throw new PackageContainsSeveralBrickException($package, $brick->getName(), $ref->getName());
                }
            }

            $class_map[] = $ref;
        }

        if ($brick === null) {
            throw new PackageContainsNoBrickException($package);
        }

        return new BrickPresenter(
            $package,
            $brick,
            $class_map,
        );
    }
}
