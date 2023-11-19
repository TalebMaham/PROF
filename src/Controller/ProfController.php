<?php

namespace App\Controller;

use App\Repository\MatiereRepository;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Mpdf\Mpdf;
use TomasVotruba\BarcodeBundle\Generator\BarcodeDataGenerator;
use TomasVotruba\BarcodeBundle\Generator\BarcodeGenerator;
use TomasVotruba\BarcodeBundle\Generator\ImageRenderer;

#[Route(path: '/prof')]
class ProfController extends AbstractController
{
    #[Route('/eleves', name: 'app_prof')]
 
    public function index(Request $request, UserRepository $userRepository, NoteRepository $noteRepository, MatiereRepository $matiereRepository): Response
    {
         // Vérifiez si l'utilisateur est authentifié
         if ($this->isGranted('ROLE_STUDENT')) {
            // Redirigez vers la page de connexion
            return $this->redirectToRoute('app_student');
        }
        // Vérifiez si l'utilisateur est authentifié
        if ($this->isGranted('ROLE_PROF')) {
            // Redirigez vers la page de connexion
            $eleves = $userRepository->findByRole("ROLE_STUDENT"); 
            $notes = $noteRepository->findAll(); 
            $matiers = $matiereRepository->findAll(); 
  

        return $this->render('prof/index.html.twig', [
            'controller_name' => 'ProfController',
            'eleves' => $eleves,
            'notes' => $notes,
            'matieres' => $matiers,
            'user'=> $this->getUser()
        ]);
        
    }
        return $this->redirectToRoute('app_login');
    }


    #[Route( path : '_searchSelect', name: 'app_prof_id')]
    public function student(Request $request, UserRepository $userRepository): Response
    {
        $output = [];
        $search = $request->get('q'); 
        $etudiants = $userRepository->search($search); 
            
        return new JsonResponse($etudiants[0]); 
    }


    #[Route( path : '_presence', name: 'prise_presence')]
    public function presence(Request $request, UserRepository $userRepository): Response
    {
        $output = [];
        $eleve_id = $request->get('eleve_id');
        $is_present = $request->get('is_present');
        $etudiants = $userRepository->findBy(['id' => $eleve_id]);
         
        if($is_present)
        {
            $status = "Present";
            $etudiants[0]->setPresent(true); 
            return $this->json([
                'success' => true,
                'status' => $status
            ]);
        }

       
          $status = "Absent";
          $etudiants[0]->setPresent(false); 
          return $this->json([
            'success' => true,
            'status' => $status
        ]);
    }

    #[Route( path : '/logout', name: 'app_logout')]
    public function logout()
    {
        
    }


    
    /**
     * @Route("/generate-pdf", name="generate_pdf")
     */
    public function generatePdf()
    {
        $pdf = new Mpdf();
        $text = "<h1> Bonjour".$this->getUser()->getNom()."Voici ton rélevé de note"; 
        $pdf->WriteHTML("
        <!DOCTYPE html>
<html>
<head>
    <title>Relevé de Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>Relevé de Notes</h1>
        <h2>Étudiant: John Doe</h2>
        <table>
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mathématiques</td>
                    <td>85</td>
                </tr>
                <tr>
                    <td>Histoire</td>
                    <td>70</td>
                </tr>
                <tr>
                    <td>Sciences</td>
                    <td>92</td>
                </tr>
                <!-- Ajoutez d'autres lignes pour chaque matière et note -->
            </tbody>
        </table>
        <h3>Moyenne Générale: 82</h3>
    </div>
</body>
</html>

        
        ");

        $response = new Response($pdf->Output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"',
        ]);

        return $response;
    }


     
    /**
     * @Route("/generate-codebar", name="generate_codebar")
     */
    public function generateQrCode(): Response
    {
        // Supposons que vous avez un tableau associatif avec les relevés de notes de l'étudiant
        $studentGrades = [
            'math' => 95,
            'science' => 88,
            'history' => 75,
            // ... d'autres matières et notes
        ];

        // Convertir les données en format JSON
        $jsonData = json_encode($studentGrades);

        $size = 200; // La taille du code QR

        $url = sprintf('https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s', $size, $size, urlencode($jsonData));

        return $this->render('prof/generate_codebar.html.twig', [
            'qrCodeUrl' => $url,
        ]);
    }

}
