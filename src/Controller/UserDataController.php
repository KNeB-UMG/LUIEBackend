<?php

namespace App\Controller;

use App\Entity\UserData;
use App\Service\JWTService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserDataController extends AbstractController
{
    private JWTService $jwtService;
    public function __construct(JWTService $jwtService){
        $this->jwtService = $jwtService;
    }
    function getRandomNames(): array
    {
        $normalInterfaceArray=[
            'UI1','UI2','UI3','UI4','UI5',
            'UI6','UI7','UI8','UI9','UI10',
            'UI11','UI12','UI13','UI14','UI15'
        ];
        $delayedInterfaceArray=[
            'LUI1','LUI2','LUI3','LUI4','LUI5',
            'LUI6','LUI7','LUI8','LUI9','LUI10',
            'LUI11','LUI12','LUI13','LUI14','LUI15'
        ];
        shuffle($normalInterfaceArray);
        shuffle($delayedInterfaceArray);

        $selectedFirst = array_slice($normalInterfaceArray, 0, 5);
        $selectedSecond = array_slice($delayedInterfaceArray, 0, 5);

        $combined = array_merge($selectedFirst, $selectedSecond);

        shuffle($combined);

        return $combined;
    }

    #[Route('/api/createentry', name: 'create_entry', methods: 'POST')]
    public function createEntry(Request $request,EntityManagerInterface $entityManager):JsonResponse{
        $data = json_decode($request->getContent(), true);
        if(!isset($data['email'],$data['age'],$data['gender'],$data['loe'])){
            return new JsonResponse(['status' => 'Email not provided'], Response::HTTP_BAD_REQUEST);
        }
        $email = strtolower($data['email']);
        if (!$email || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }
        $userDataRepository = $entityManager->getRepository(UserData::class);
        $hash = hash('sha256', strtolower($email));
        $userData = $userDataRepository->findOneBy(['id' => $hash]);
        if(!$userData){

        }
        $userData = new UserData();
        $userData->setIdFromEmail($email);
        $userData->setAge($data['age']);
        $userData->setGender($data['gender']);
        $userData->setLoe($data['loe']);
        $namesArray = $this->getRandomNames();
        $dataArray=[];
        $memory=1;
        foreach($namesArray as $name){
            $data=[
                'stepNumber'=>$memory,
                'name' => $name,
                'timeStarted'=>null,
                'timeEnded'=>null,
                'grade1'=>null,
                'grade2'=>null,
                'grade3'=>null,
            ];
            $memory++;
            $dataArray[]=$data;
        }
        $userData->setData([
            'currentStep'=>$namesArray[0],
            'data'=>$dataArray,
            'completed'=>false,
        ]);
        $token = $this->jwtService->createToken($hash,$data['age'],$data['gender'],$data['loe'],$namesArray);

        $entityManager->persist($userData);
        $entityManager->flush();
        return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_OK);

    }
}
