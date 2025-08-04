<?php
include '_layout.php';
?>
<style>

    .container {
      max-width: 400px;
      margin: 100px auto;
      text-align: center;
      border: 2px solid #28a745;
      border-radius: 12px;
      padding: 50px 40px;
      background-color: #f8fff9;
      
    }

    h1 {
      color: #333;
      font-size: 2.5rem;
      margin-bottom: 10px;
      font-weight: 300;
    }

    h2 {
      color: #666;
      font-size: 1.2rem;
      margin-bottom: 30px;
      font-weight: 400;
    }

    p {
      color: #666;
      font-size: 1rem;
      margin-bottom: 40px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }

    .take {
      display: inline-block;
      padding: 15px 30px;
      font-size: 1.5rem;
      color: white;
      background-color: #28a745;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s ease;
      font-weight: 500;
      border: 2px solid #28a745;
    }

    .btn:hover {
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    @media screen and (max-width: 600px) {
      .container {
        max-width: 300px;
      }
      .take {
        display: block;
        width: 100%;
        padding: 18px 0;
        font-size: 1rem;
        box-sizing: border-box;
        margin-top: 18px;
      }
      .container {
        padding: 25px 6px;
        border-radius: 8px;
        margin: 30px auto;
      }
      h1 {
        font-size: 2rem;
      } 
    }

  </style>


  <div class="container">
    <h1>ISU-R Library Survey</h1>
    <h2>Help us improve your experience</h2>
    
    <p>We'd love to hear your thoughts! Your feedback helps us create a better library experience for everyone. The survey takes just a few minutes and is completely anonymous.</p>

    <a class="take" href="https://docs.google.com/forms/d/e/1FAIpQLSduOSnigxIj-a6c9YwVLWkeQ7erdOcsc8KH075WsDn8QrT34Q/viewform?usp=header" target="_blank">Take Survey</a>
  </div>


