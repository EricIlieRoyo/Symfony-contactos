<?php

namespace App\Controller;

use App\Entity\Contacto;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactoFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class ContactoController extends AbstractController
{
    #[Route('/contacto/{codigo}', name: 'contacto', requirements: ['codigo' => '[0-9]+'])]
    public function ficha(ManagerRegistry $doctrine, int $codigo = 1): Response
    {
        // La primera instrucción suele ser esta, ya que cogemos el repositorio de la entidad asociada
        $repositorio = $doctrine->getRepository(Contacto::class);
        // Ahora usamos uno de los métodos del repositorio
        $contacto = $repositorio->find($codigo);
        
        return $this->render('ficha.html.twig',[
            "contacto" => $contacto
        ]);
    }
// #[Route('/contacto/nuevo/{nombre}/{telefono}/{email}', name: 'nuevo-con-datos')]
// public function nuevoContacto(
//     ManagerRegistry $doctrine,
//     string $nombre,
//     string $telefono,
//     string $email,
// ){
//     $contacto = new Contacto();
//     $contacto->setNombre($nombre);
//     $contacto->setTelefono($telefono);
//     $contacto->setEmail($email);
//     
//     // guardamos el objeto
//     $entityManager = $doctrine->getManager();
//     $entityManager->persist($contacto);
//     $entityManager->flush();

//     //volvemos a la ficha del nuevo contacto
//     return $this->redirectToRoute('contacto',[
//         "codigo" => $contacto->getId()
//     ]);
//     
// }
    #[Route('/contacto/modificar/{codigo}/{nombre_nuevo}', name: 'cambiar-nombre')]
    public function modificar(ManagerRegistry $doctrine, int $codigo, string $nombre_nuevo)
    {
        if (!$this->getUser()) {
            return $this->redirect('/index');
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
    public function borrar(ManagerRegistry $doctrine, int $codigo)
    {
        if (!$this->getUser()) {
            return $this->redirect('/index');
        }
        $contacto = $doctrine->getRepository(Contacto::class)->find($codigo);
        if ($contacto){
            $entityManager = $doctrine->getManager();
            try{
                $entityManager->remove($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('inicio');
            }catch (\Exception $e){
                error_log("Error insertando objeto " . $e->getMessage());
                return new Response("Error insertando objeto " . $e->getMessage());
            }
        }else{
            return new Response("No se ha encontrado el contacto");
        }    
    }

    #[Route('/contacto/nuevo', name: 'nuevo')]
    public function nuevo(ManagerRegistry $doctrine, Request $request)
    {
        if (!$this->getUser()) {
            return $this->redirect('/index');
        }
        $contacto = new Contacto();
        $formulario = $this->createForm(ContactoFormType::class, $contacto);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            if ($formulario->has('borrar') && $formulario->get('borrar')->isClicked()) {
                return $this->redirectToRoute('inicio');
            }
            $contacto = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($contacto);
            $entityManager->flush();
            return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
        }
        return $this->render('nuevo.html.twig', array('formulario' => $formulario->createView()));
    }

    #[Route('/contacto/editar/{codigo}', name: 'editar', requirements:["codigo"=>"\d+"])]
    public function editar(ManagerRegistry $doctrine, Request $request, int $codigo) {
        if (!$this->getUser()) {
            return $this->redirect('/index');
        }
        $repositorio = $doctrine->getRepository(Contacto::class);
        //En este caso, los datos los obtenemos del repositorio de contactos
        $contacto = $repositorio->find($codigo);
        if ($contacto){
            // A partir de $contacto, rellena automáticamente el formulario
            $formulario = $this->createForm(ContactoFormType::class, $contacto);

            $formulario->handleRequest($request);

            if ($formulario->isSubmitted() && $formulario->isValid()) {
                $entityManager = $doctrine->getManager();

                // Comprobamos si el usuario ha pulsado el botón de borrar
                if ($formulario->has('borrar') && $formulario->get('borrar')->isClicked()) {
                    $entityManager->remove($contacto);
                    $entityManager->flush();
                    return $this->redirectToRoute('inicio');
                }

                // Si ha pulsado en guardar (o editar)
                $contacto = $formulario->getData();
                $entityManager->persist($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
            }

            // Ponemos los datos del contacto
            return $this->render('editar.html.twig', array(
                'formulario' => $formulario->createView()
            ));
        }else{
            return $this->render('ficha.html.twig', [
                'contacto' => NULL
            ]);
        }
    }
}