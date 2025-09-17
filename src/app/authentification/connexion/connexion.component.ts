import { Component } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterOutlet } from '@angular/router';
@Component({
  selector: 'app-connexion',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterOutlet],
  templateUrl: './connexion.component.html'
})
export class ConnexionComponent {
  pseudo = '';
  email = '';
  password = '';
  errorMessage = 'veuillez contacter le service';

  constructor(private api: ApiService) {}

  login() {
    if (!this.pseudo || !this.password) {
      this.errorMessage = 'Veuillez remplir tous les champs';
      return;
    }
  }
}
