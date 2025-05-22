import { Component } from '@angular/core';
import { Router, RouterOutlet } from '@angular/router';
import { AuthService } from '../authentification';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-inscription',
  imports: [FormsModule,CommonModule,RouterOutlet],
  templateUrl: './inscription.component.html',
  styleUrls: ['./inscription.component.scss']
})
export class InscriptionComponent {
  username: string = '';
  password: string = '';
  errorMessage: string = '';

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    if (this.username && this.password) {
      this.authService.register(this.username, this.password).subscribe(
        () => {
          this.router.navigate(['/connexion']);
        },
        (error) => {
          this.errorMessage = 'Erreur lors de l\'inscription, veuillez réessayer';
        }
      );
    } else {
      this.errorMessage = 'Veuillez remplir tous les champs';
    }
  }
}
