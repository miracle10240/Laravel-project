<?php

namespace App\Http\Livewire\Posts;

use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Post;
use App\Models\Media;
use App\Models\Like;
use App\Models\Comment;
use Auth;

class View extends Component
{
    use WithPagination;
    
    public $comments = [];
    
    public $comment;
    
    public $postId;
    
    public $deletePostId;
    
    public $isOpenCommentModal = false;
    
    public $isOpenDeletePostModal = false;
    
    public function render()
    {
        $posts = Post::withCount(['likes', 'comments'])->with(['userLikes', 'user' => function ($query) {
            $query->select('id', 'name');
        }])->latest()->paginate(10);
        return view('livewire.posts.view', ['posts' => $posts]);
    }
    
    public function incrementLike(Post $post)
    {
        $like = Like::where('user_id', Auth::id())
                    ->where('post_id', $post->id);
                    
        if (!$like->count()) {
            $new = Like::create([
                'post_id' => $post->id,
                'user_id' => Auth::id(),
            ]);
            
            return true;
        } else {
            $like->delete();
        }
    }
    
    public function comments(Post $post)
    {
        $this->postId = $post->id;
        $this->resetValidation('comment');
        $this->isOpenCommentModal = true;
        $this->setComments($post);
        return true;
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function createComment(Post $post)
    {
        $validatedData = Validator::make(
            ['comment' => $this->comment],
            ['comment' => 'required|max:5000']
        )->validate();
        
        Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'comment' => $validatedData['comment'],
        ]);
        
        session()->flash('comment.success', 'Comment created successfully');
        
        $this->setComments($post);
        $this->comment = '';
        
        //$this->isOpenCommentModal = false;
        return redirect()->back();
    }
  
    
    public function setComments($post)
    {
        $this->comments = $post->comments;
        return true;
    }
    
    
    public function showDeletePostModal(Post $post)
    {
    	$this->deletePostId = $post->id;
    	$this->isOpenDeletePostModal = true;
    }
    
    
    public function deletePost(Post $post)
    {
    	if(Auth::user()->role_id === 2 || $post->user->id === Auth::id())
	    {
		 try{
			$post->delete();
			session()->flash('success', 'Post deleted successfully');
            
		} catch(\Exception $e) {
			session()->flash('error', 'Cannot delete post');
            
           }
		} else {
			session()->flash('error', 'Action not permitted');
            
		}
		$this->isOpenDeletePostModal = false;
		return redirect()->back();
    }
    
    
    public function deleteComment(Post $post, Comment $comment)
    {
        if (Auth::user()->role_id === 2 || $comment->user->id === Auth::id() || $post->user->id === Auth::id()) {
            $comment->delete();
	        $this->isOpenCommentModal = false;
	        session()->flash('success', 'Comment deleted successfully');
        } else {
	        session()->flash('comment.error', 'You can only delete your comments.');
		}
		
        return redirect()->back();
    }
}
