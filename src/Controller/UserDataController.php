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
        if (!isset($data['email'], $data['age'], $data['gender'], $data['loe'])) {
            return new JsonResponse(
                ['error' => 'Missing required fields. Required: email, age, gender, loe'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $age = $data['age'];
        $gender = $data['gender'];
        $loe = $data['loe'];
        $email = strtolower($data['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(
                ['error' => 'Invalid email format'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $userDataRepository = $entityManager->getRepository(UserData::class);
        $hash = hash('sha256', strtolower($email));
        $userData = $userDataRepository->findOneBy(['id' => $hash]);
        if($userData){
            if($userData->getData()['completed']){
                return new JsonResponse(
                    ['error' => 'test already completed'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $dataArray=$userData->getData();
            $namesArray=[];
            foreach($dataArray['data'] as $d){
                $namesArray[]=$d['name'];
            }
            $token = $this->jwtService->createToken($hash,$age,$gender,$loe,$namesArray);

            $entityManager->persist($userData);
            $entityManager->flush();
            return new JsonResponse(
                ['status' => 'succesfully renewed session',
                    'currentStep'=>$namesArray[0],
                    'token'=>$token
                ], Response::HTTP_OK);
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

        $token = $this->jwtService->createToken($hash,$age,$gender,$loe,$namesArray);

        $entityManager->persist($userData);
        $entityManager->flush();
        return new JsonResponse(
            ['status' => 'succesfully created entry',
            'currentStep'=>$namesArray[0],
            'token'=>$token
        ], Response::HTTP_OK);

    }

    #[Route('/api/sendevaluation', name: 'send_evaluation', methods: 'POST')]
    public function receiveEvaluationData(Request $request,EntityManagerInterface $entityManager,JWTService $jwtService):JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['userId'], $data['interfaceName'], $data['grade1'], $data['grade2'], $data['grade3'])) {
            return new JsonResponse(
                ['error' => 'Missing required fields.'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $userDataRepository = $entityManager->getRepository(UserData::class);
        $userData = $userDataRepository->findOneBy(['id' => $data['userId']]);
        if(!$userData){
            return new JsonResponse(['error'=>'user not found'],Response::HTTP_NOT_FOUND);
        }

        $dataArray = $userData->getData();
        $flag = false;
        $gradeArray=[$data['grade1'],$data['grade2'],$data['grade3']];
        foreach($gradeArray as $grade){
            if(!($grade<=5&&$grade>=0)){
                return new JsonResponse(['error'=>'invalid grade'],Response::HTTP_BAD_REQUEST);
            }
        }
        if($dataArray['currentStep']!=$data['interfaceName'])
        {
            return new JsonResponse(['error'=>'invalid step'],Response::HTTP_BAD_REQUEST);
        }
        $now = new \DateTimeImmutable();;
        $memory = 0;
        $step = null;
        foreach ($dataArray['data'] as &$d){
            $memory++;
            if($d['timeEnded']){
                continue;
            }
            if($flag){
                $dataArray['currentStep']=$d['name'];
                $step=$d['name'];
                $d['timeStarted'] = $now->format('Y-m-d H:i:s');
                break;
            }
            if($dataArray['currentStep']==$d['name']){
              $d['timeEnded'] = $now->format('Y-m-d H:i:s');
              $flag = true;
              $d['grade1']=$data['grade1'];
              $d['grade2']=$data['grade2'];
              $d['grade3']=$data['grade3'];
            }
            if($memory==10){
                $dataArray['completed']=true;
            }
        }
        $userData->setData($dataArray);
        $entityManager->persist($userData);
        $entityManager->flush();
        if($dataArray['completed']){
            return new JsonResponse(['status' => 'test completed'], Response::HTTP_OK);
        }
        return new JsonResponse(['status' => $step], Response::HTTP_OK);
    }

}
