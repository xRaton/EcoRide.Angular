import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-reviews',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reviews.component.html'
})
export class ReviewsComponent implements OnInit {
  reviews: any[] = [];
  userId = 0; // à remplacer par l'ID de l'utilisateur ciblé
  newReview = {
    ride_id: 0,
    target_user_id: 0,
    note: 5,
    commentaire: ''
  };

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    if (this.userId) {
      this.api.getReviewsForUser(this.userId)
        .subscribe(res => this.reviews = res);
    }
  }

  postReview() {
    this.api.postReview(this.newReview).subscribe(() => {
      alert('Avis ajouté');
      this.api.getReviewsForUser(this.userId)
        .subscribe(res => this.reviews = res);
    });
  }
}

