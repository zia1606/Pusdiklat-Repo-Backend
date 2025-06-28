#!/usr/bin/env python3
"""
Sistem Rekomendasi Koleksi
Script untuk mencari kesamaan dokumen berdasarkan preprocessing content
"""

import sys
import json
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import warnings

# Suppress warnings
warnings.filterwarnings('ignore')

def load_data_from_json(json_file):
    """Load data from JSON file"""
    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
        return data
    except Exception as e:
        print(json.dumps({'error': f'Failed to load data: {str(e)}'}))
        sys.exit(1)

def find_similar_collections(data, top_n=10):
    """Find similar collections using TF-IDF and cosine similarity"""
    try:
        collections = data['collections']
        reference_index = data['reference_index']
        
        if reference_index == -1:
            return {'error': 'Reference collection not found'}
        
        # Convert to DataFrame
        df = pd.DataFrame(collections)
        
        # Check if we have enough data
        # if len(df) < 2:
        #     return {'error': 'Not enough data for recommendation'}
        
        # Prepare text data
        texts = df['preprocessing'].fillna('').astype(str)
        
        # Check if reference text is not empty
        if not texts.iloc[reference_index].strip():
            return {'error': 'Reference collection has empty preprocessing data'}
        
        # Calculate TF-IDF matrix
        tfidf_vectorizer = TfidfVectorizer(
            # max_features=5000,
            # stop_words=None,  # You can add Indonesian stop words here
            # ngram_range=(1, 2),
            # min_df=1,
            # max_df=0.95
        )
        
        tfidf_matrix = tfidf_vectorizer.fit_transform(texts)
        
        # Get reference vector
        ref_vector = tfidf_matrix[reference_index:reference_index+1]
        
        # Calculate cosine similarity
        sim_scores = cosine_similarity(ref_vector, tfidf_matrix)
        
        # Create results DataFrame
        results = df.copy()
        results['similarity'] = sim_scores[0]
        
        # Remove reference document and sort by similarity
        results = results.drop(reference_index).sort_values('similarity', ascending=False)
        
        # Get top N results
        top_results = results.head(top_n)
        
        # Format output
        recommendations = []
        for _, row in top_results.iterrows():
            recommendations.append({
                'id': int(row['id']),
                'judul': row['judul'],
                'penulis': row['penulis'],
                'kategori': row['kategori'],
                'jenis_dokumen': row['jenis_dokumen'],
                'tahun_terbit': row['tahun_terbit'],
                'views': int(row['views']),
                'similarity_score': float(row['similarity'])
            })
        
        # Reference collection info
        ref_collection = df.iloc[reference_index]
        reference_info = {
            'id': int(ref_collection['id']),
            'judul': ref_collection['judul'],
            'penulis': ref_collection['penulis'],
            'kategori': ref_collection['kategori'],
            'jenis_dokumen': ref_collection['jenis_dokumen']
        }
        
        return {
            'success': True,
            'reference_collection': reference_info,
            'recommendations': recommendations,
            'total_found': len(recommendations),
            'total_processed': len(df) - 1  # Exclude reference
        }
        
    except Exception as e:
        return {'error': f'Processing failed: {str(e)}'}

def main():
    """Main function"""
    if len(sys.argv) < 3:
        print(json.dumps({'error': 'Usage: python recommendation.py <data_file> <top_n>'}))
        sys.exit(1)
    
    data_file = sys.argv[1]
    try:
        top_n = int(sys.argv[2])
    except ValueError:
        top_n = 10
    
    # Load data
    data = load_data_from_json(data_file)
    
    # Find similar collections
    result = find_similar_collections(data, top_n)
    
    # Output result as JSON
    print(json.dumps(result, ensure_ascii=False, indent=None))

if __name__ == '__main__':
    main()