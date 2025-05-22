import { Component } from '@angular/core';
import { AuthService } from '../authentification';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router, RouterLink, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-connexion',
  imports: [
    FormsModule,
    CommonModule,
    RouterOutlet,
  ],
  templateUrl: './connexion.component.html',
  styleUrl: './connexion.component.scss'
})
export class ConnexionComponent {
  username: string = '';
  password: string = '';
  errorMessage: string = '';

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    if (this.username && this.password) {
      this.authService.login(this.username, this.password).subscribe(
        (response) => {
          this.authService.setToken(response.token);
          this.router.navigate(['/']); // Rediriger vers la page d'accueil
        },
        (error) => {
          this.errorMessage = 'Nom d\'utilisateur ou mot de passe incorrect';
        }
      );
    } else {
      this.errorMessage = 'Veuillez remplir tous les champs';
    }
  }
}