<?php
declare(strict_types=1);

namespace Bulakh\Services;

require __DIR__ . '/../../vendor/autoload.php';

use Bulakh\Models\Booking;
use Bulakh\Models\Provider;
use Bulakh\Infrastructure\ProvidersRepository;
use Bulakh\Models\Registration;
use React\Promise\Deferred;
use React\EventLoop\Factory;

class RegisterService
{
    protected static $pendingRegistrationTasks = [];

    public static function registerBooking(Booking $booking)
    {
        $loop = Factory::create();

        /** @var Provider $provider */
        foreach (ProvidersRepository::getArray() as $provider) {
            $taskDeferred = new Deferred();
            $taskDeferred->promise()
                ->done(
                    function() use ($booking, $provider) {
                        $booking->addProvider($provider);
                        LoggingService::getLogger()
                            ->info("Registered", [$booking->getBookingNumber(), $provider->getId()]);
                    },
                    function() use ($booking, $provider) {
                        LoggingService::getLogger()
                            ->info("Registration failed", [$booking->getBookingNumber(), $provider->getId()]);
                    }
                );

            self::$pendingRegistrationTasks[] = $taskDeferred;

            $registration = new Registration($booking, $provider);
            $registration->register($loop, $taskDeferred);
        }

        $loop->run();
    }
}
