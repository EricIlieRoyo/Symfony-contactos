<?php

namespace App\Controller;

use App\Entity\Contacto;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactoController extends AbstractController
{
#[Route('/contacto/{codigo}', name: 'contacto')]
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
#[Route('/contacto/nuevo/{nombre}/{telefono}/{email}', name: 'nuevo-con-datos')]
public function nuevoContacto(
    ManagerRegistry $doctrine,
    string $nombre,
    string $telefono,
    string $email,
){
    $contacto = new Contacto();
    $contacto->setNombre($nombre);
    $contacto->setTelefono($telefono);
    $contacto->setEmail($email);
    
    // guardamos el objeto
    $entityManager = $doctrine->getManager();
    $entityManager->persist($contacto);
    $entityManager->flush();

    //volvemos a la ficha del nuevo contacto
    return $this->redirectToRoute('contacto',[
        "codigo" => $contacto->getId()
    ]);
    
}
#[Route('/contacto/modificar/{codigo}/{nombre_nuevo}', name: 'cambiar-nombre')]
public function modificar(ManagerRegistry $doctrine, int $codigo, string $nombre_nuevo)
{
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
}