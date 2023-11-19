<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Transfer;

class PaymentController extends AbstractController
{
    /**
     * @Route("/form-payment", name="form_payment")
     */
    public function paymentForm(): Response
    {
        $dotenv = new Dotenv();
        $dotenv->load($this->getParameter('kernel.project_dir') . '/.env');
        dd($this->getParameter('stripe_public_key'));
        return $this->render('payment/payment_form.html.twig', [
            'publicKey' => $this->getParameter('stripe_public_key'),
        ]);
    }

    /**
     * @Route("/payment", name="process_payment", methods={"POST"})
     */
    public function processPayment(Request $request): Response
    {
        $token = $request->request->get('stripeToken');
        $destinationAccountId = 'destination_account_id'; // Replace with the actual destination account ID

        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY']; // Access the secret key directly from environment variables

        Stripe::setApiKey($stripeSecretKey);

        // Create a charge
        $charge = Charge::create([
            'amount' => 1000,
            'currency' => 'usd',
            'source' => $token,
            'description' => 'Purchase on my website',
            'transfer_data' => [
                'destination' => $destinationAccountId,
            ],
        ]);

        // Create a transfer
        $transfer = Transfer::create([
            'amount' => 800,
            'currency' => 'usd',
            'destination' => $destinationAccountId,
            'transfer_group' => 'order_123',
        ]);

        // You can add error handling and notifications here

        return $this->render('payment/payment_success.html.twig');
    }
}