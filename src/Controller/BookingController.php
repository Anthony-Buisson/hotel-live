<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Room;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\CustomerRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class BookingController extends AbstractController
{
    /**
     * @Route("/booking", name="booking")
     */
    public function index(Request $request, BookingRepository $bookingRepository, CustomerRepository $customerRepository): Response
    {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);

        $form->handleRequest($request);
        // $booking->setCreatedAt(new DateTime());

        if($form->isSubmitted() && $form->isValid()) {

            $check = $bookingRepository->checkDispo($booking);

            if($check) {
                //check Customer if exist
                $customer = $customerRepository->findOneBy([
                    'email' => $booking->getCustomer()->getEmail(),
                ]);
                if($customer) {
                    $booking->setCustomer($customer);
                }
                //save
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($booking);
                $manager->flush();
    
                $this->addFlash('success', 'Le booking à bien été créer');
    
                return $this->redirectToRoute('default');
            }else {
                $this->addFlash('danger', 'Les dates sont indisponibles');
            }
        }

        return $this->render('booking/booking.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/booking/unvailable_date/{idRoom}", name="unvailableDateByRoom", methods={"POST"})
     * 
     * @param int               $idRoom
     * @param BookingRepository $bookingRepository
     * @param BookingService    $bookingService
     * 
     * @return JsonResponse
     */
    public function getUnvailableDateByRoom($idRoom, BookingRepository $bookingRepository, BookingService $bookingService)
    {
        $bookings = $bookingRepository->getBookingsByRoom($idRoom);

        $dates = $bookingService->getBookingsRangeDates($bookings);

        return new JsonResponse($dates);
    }
}
