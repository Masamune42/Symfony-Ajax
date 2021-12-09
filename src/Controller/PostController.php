<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(PostRepository $repo)
    {
        return $this->render('post/index.html.twig', [
            'posts' => $repo->findAll(),
        ]);
    }

    /**
     * Permet de liker ou unliker un article
     * 
     * @Route("/post/{id}/like", name="post_like")
     * 
     * @param Post $post
     * @param ObjectManager $manager
     * @param PostLikeRepository $likeRepo
     * @return Response
     */
    public function like(Post $post, ObjectManager $manager, PostLikeRepository $likeRepo): Response
    {
        // On récupère le user connecté
        $user = $this->getUser();
        // Si aucun user connecté, on envoie un code d'erreur
        if (!$user) return $this->json([
            'code' => 403,
            'message' => 'Unauthorized'
        ], 403);

        // Si le post est liké par un user
        if ($post->isLikedByUser($user)) {

            // On récupère les informations sur le like par post + user
            $like = $likeRepo->findOneBy([
                'post' => $post,
                'user' => $user
            ]);
            // On supprime le like et on l'envoie en BDD
            $manager->remove($like);
            $manager->flush();

            // On retourne les infos au format json
            return $this->json(
                [
                    'code' => 200,
                    'message' => 'Like bien supprimé',
                    'likes' => $likeRepo->count(['post' => $post])
                ],
                200
            );
        }

        // On crée le like
        $like = new PostLike();
        // On lie le like au post + user
        $like->setPost($post)
            ->setUser($user);

        // On le persiste en BDD
        $manager->persist($like);
        $manager->flush();
        return $this->json(
            [
                'code' => 200,
                'message' => 'Like bien ajouté',
                'likes' => $likeRepo->count(['post' => $post])
            ],
            200
        );
    }
}
