<?php

namespace App\Controller;

use App\Entity\Contacto;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactoFormType;
use Symfony\Component\HttpFoundation\Request;

final class ContactoController extends AbstractController
{
    #[Route('/contacto/{codigo}', name: 'contacto', requirements: ['codigo' => '[0-9]+'])]
    public function ficha(ManagerRegistry $doctrine, Request $request, int $codigo = 1): Response
    {
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);
        
        if ($contacto) {
            $formulario = $this->createForm(ContactoFormType::class, $contacto);
            $formulario->handleRequest($request);

            if ($formulario->isSubmitted() && $formulario->isValid()) {
                // Comprobar que el usuario está logeado para guardar o borrar
                if (!$this->getUser()) {
                    return $this->redirectToRoute('index');
                }

                $entityManager = $doctrine->getManager();

                // Comprobar si el usuario pulsó en Borrar
                if ($formulario->get('delete')->isClicked()) {
                    $entityManager->remove($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute('index');
                }

                // Comprobar si el usuario pulsó en Guardar/Modificar
                if ($formulario->get('save')->isClicked()) {
                    $contacto = $formulario->getData();
                    $entityManager->persist($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
                }
            }

            return $this->render('ficha.html.twig', [
                "contacto" => $contacto,
                "formulario" => $formulario->createView()
            ]);
        }

        return $this->render('ficha.html.twig', [
            "contacto" => null
        ]);
    }

    #[Route('/contacto/modificar/{codigo}/{nombre_nuevo}', name: 'cambiar-nombre')]
    public function modificar(ManagerRegistry $doctrine, int $codigo, string $nombre_nuevo): Response
    {
        // Comprobar que el usuario está logeado
        if (!$this->getUser()) {
            return $this->redirectToRoute('index');
        }

        $contacto = $doctrine->getRepository(Contacto::class)->find($codigo);
        if ($contacto){
            $contacto->setNombre($nombre_nuevo);
            $entityManager = $doctrine->getManager();
            try{
                $entityManager->persist($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('contacto',["codigo" =>$contacto->getId()]);
            }catch (\Exception $e) {
                error_log("Error insertando objeto " . $e->getMessage());
                return new Response("Error insertando objeto " . $e->getMessage());
            }
        }
        return $this->redirectToRoute('contacto', ["codigo"=> null]);
    }

    #[Route('/contacto/borrar/{codigo}', name: 'borrar')]
    public function borrar(ManagerRegistry $doctrine, int $codigo): Response
    {
        // Comprobar que el usuario está logeado
        if (!$this->getUser()) {
            return $this->redirectToRoute('index');
        }

        $contacto = $doctrine->getRepository(Contacto::class)->find($codigo);
        if ($contacto){
            $entityManager = $doctrine->getManager();
            try{
                $entityManager->remove($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('index');
            }catch (\Exception $e){
                error_log("Error borrando objeto " . $e->getMessage());
                return new Response("Error borrando objeto " . $e->getMessage());
            }
        }else{
            return new Response("No se ha encontrado el contacto");
        }    
    }

    #[Route('/contacto/nuevo', name: 'nuevo')]
    public function nuevo(ManagerRegistry $doctrine, Request $request): Response
    {
        // Comprobar que el usuario está logeado
        if (!$this->getUser()) {
            return $this->redirectToRoute('index');
        }

        $contacto = new Contacto();
        $formulario = $this->createForm(ContactoFormType::class, $contacto);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            if ($formulario->get('delete')->isClicked()) {
                return $this->redirectToRoute('index');
            }

            if ($formulario->get('save')->isClicked()) {
                $contacto = $formulario->getData();
                $entityManager = $doctrine->getManager();
                $entityManager->persist($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
            }
        }

        return $this->render('nuevo.html.twig', array('formulario' => $formulario->createView()));
    }

    #[Route('/contacto/editar/{codigo}', name: 'editar', requirements: ["codigo" => "\d+"])]
    public function editar(ManagerRegistry $doctrine, Request $request, int $codigo): Response
    {
        // Comprobar que el usuario está logeado
        if (!$this->getUser()) {
            return $this->redirectToRoute('index');
        }

        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);
        if ($contacto){
            $formulario = $this->createForm(ContactoFormType::class, $contacto);
            $formulario->handleRequest($request);

            if ($formulario->isSubmitted() && $formulario->isValid()) {
                $entityManager = $doctrine->getManager();

                if ($formulario->get('delete')->isClicked()) {
                    $entityManager->remove($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute('index');
                }

                if ($formulario->get('save')->isClicked()) {
                    $contacto = $formulario->getData();
                    $entityManager->persist($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
                }
            }

            return $this->render('editar.html.twig', array(
                'formulario' => $formulario->createView(),
                'contacto' => $contacto
            ));
        }else{
            return $this->render('ficha.html.twig', [
                'contacto' => NULL
            ]);
        }
    }
}