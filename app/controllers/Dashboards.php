<?php

class Dashboards extends Controller{
  private $table;

  public function __construct(){
    $this->dashboardModel = $this->model('Dashboard');
  }

  // What to make the index page to dashboard?
  // Maybe have no index page?
  // Api news generator?
  // All features shows in snippets?
  public function index(){
    if(!$this->authorize()){
      echo "nope";
    }

    $this->table = 'tbl_investement_strategy';

    if(empty($_POST['search'])){
      $notes = $this->getNotes($this->table);
    } else{
      $notes = $this->searchNotes($_POST['search'], $this->table);
    }

    $notes_num = $this->dashboardModel->notes_num($_SESSION['user_id']);
    $notes_num += 1;
    $data = [
      'notes' => $notes,
      'note_num' => $notes_num,
    ];
    $this->view('dashboards/index', $data);

  }

  /*
    data = (notes, table, header on top of page, repeat the method name)
  */
  public function investment_general(){
    $this->authorize();
    $this->table = 'tbl_investement_notes_general';
    $notes = $this->notes($this->table);
    $data = $this->data($notes, $this->table, 'Investing - General', 'investment_general');
    $this->view('dashboards/index', $data);
  }

  public function developing_general(){
    $this->authorize();
    $this->table = 'tbl_developing_notes_general';
    $notes = $this->notes($this->table);
    $data = $this->data($notes, $this->table, 'Developing - General', 'developing_general');
    $this->view('dashboards/index', $data);
  }

  public function data($notes, $table, $header, $method){
    return $data = [
      'notes' => $notes,
      'table' => $table,
      'header' => $header,
      'method' => $method
    ];
  }

  // Checks to see if user searched, Returns all notes or just searched notes
  public function notes($table){
    if(empty($_POST['search'])){
      return $notes = $this->getNotes($table);
    } else{
      return $notes = $this->searchNotes($_POST['search'], $table);
    }
  }

  public function getNotes($table){
    return $this->dashboardModel->getNotes($this->table);
  }

  // returns searched notes, sets a search-word session that is printed to webpage
  public function searchNotes($search, $table){
    $search = filter_var($search, FILTER_SANITIZE_STRING);
    $_SESSION['search-word'] = $search;
    return $this->dashboardModel->searchNotes($search, $table);
  }

  // Unset the session, calls the goBack function
  public function delete_Search(){
    unset($_SESSION['search-word']);
    $this->goBack();
  }

  public function add_note($table){
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

      // Filter Input
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
      // set the data
      $data = [
        'note' => $_POST['note'],
        'title' => $_POST['title'],
        'error' => ''
      ];

      if(empty($data['note']) || empty($data['title'])){
        $data['error'] = 'Make sure both fields are filled in';
      } else{
        if($this->dashboardModel->add_note($data['note'], $data['title'], $_SESSION['user_id'], $table)){
          // Confirm note added and show new note on aside
          $this->kill_session('inside');
          $_SESSION['confirm'] = 'Note added';
          $this->goBack();
        } else{
          // Error with note
          die("Error");
        }
      }
    } else{
      // If no post method
      $this->index();
    }
  }

  public function update_note($table, $id){
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
      $data = [
        'id' => $id,
        'note' => $_POST['update'],
        'title' => $_POST['title'],
        'note_err' => '',
        'title_err' => ''
      ];

      if(empty($data['note'])){
        $data['note_err'] = 'The note is empty';
      } else if(empty($data['title'])){
        $data['title_err'] = 'The title is empty';
      }

      if(empty($data['note_err']) && empty($data['title_err'])){
        if($this->dashboardModel->update_note($data, $table)){
          $this->kill_session('inside');
          $_SESSION['confirm'] = 'Note Saved';
          $this->goBack();
        } else{
          $_SESSION['confirm'] = 'Error Updating Note';
        }
      }
    } else{
      redirect('/dashboards/index');
    }
  }

  public function delete_note($id, $table){
    if($this->dashboardModel->delete_note($id, $table)){
      $this->kill_session('inside');
      $_SESSION['confirm'] = 'Note Deleted';
      $this->goBack();
    }else{
      $_SESSION['confirm'] = 'Error Deleting Note';
    }
  }

  // Kill the confirm sessions. It takes it the location the call came from.
  // If outside then go back to previous page/
  // If inside then return true back to method that called it.
  public function kill_session($location){
    unset($_SESSION['confirm']);
    if($location = 'outside'){
      $this->goBack();
    } else{
      return true;
    }
  }

  public function logout(){
    session_destroy();
    redirect('/pages/index');
  }

  public function authorize(){
    if(!isLoggedIn()){
      redirect('/pages/index');
    } else{
      return true;
    }
  }

  // Go back to the page that submitted a form.
  public function goBack(){
    return header("Location: " . $_SERVER["HTTP_REFERER"]);
  }
}