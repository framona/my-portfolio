import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, StyleSheet, ScrollView, ActivityIndicator, SafeAreaView, Dimensions, Alert } from 'react-native';
import DropDownPicker from 'react-native-dropdown-picker';
import AsyncStorage from '@react-native-async-storage/async-storage';

const screenWidth = Dimensions.get("window").width;

export default function Statistics() {
    const [loading, setLoading] = useState(true);
    const [open, setOpen] = useState(false);
    const [value, setValue] = useState(null);
    const [items, setItems] = useState([]);
    const [stats, setStats] = useState(null);
    const [statsLoading, setStatsLoading] = useState(false);

    useEffect(() => {
        const fetchVets = async () => {
            try {
                const token = await AsyncStorage.getItem('user_token');
                const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=list_users&api_token=${token}`);
                const data = await res.json();
                
                if (data.success) {
                    const vetList = data.users
                        .filter(u => u.role === 'vet')
                        .map(v => ({ 
                            label: `${v.first_name} ${v.last_name}`, 
                            value: v.id 
                        }));
                    setItems(vetList);
                }
            } catch (err) {
                Alert.alert("Error", "Failed to load veterinarians.");
            } finally {
                setLoading(false);
            }
        };

        fetchVets();
    }, []);

    const loadStats = async (vetId) => {
        if (!vetId) return;
        setStatsLoading(true);
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=get_vet_stats&vet_id=${vetId}&api_token=${token}`);
            const data = await res.json();
            
            if (data.success) {
                setStats(data.stats);
            } else {
                Alert.alert("Error", data.error || "Could not load stats.");
            }
        } catch (err) {
            Alert.alert("Error", "Network error.");
        } finally {
            setStatsLoading(false);
        }
    };

    const getBarWidth = (val, max) => {
        if (!max || max === 0) return 0;
        const percentage = val / max;
        return percentage * (screenWidth - 80); 
    };

    if (loading) return (
        <View style={styles.center}>
            <ActivityIndicator size="large" color="#4CAF50" />
        </View>
    );

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.container}>
                <Text style={styles.title}>Visual Analytics</Text>

                <View style={[styles.dropdownContainer, { zIndex: 1000 }]}>
                    <DropDownPicker
                        open={open}
                        value={value}
                        items={items}
                        setOpen={setOpen}
                        setValue={setValue}
                        setItems={setItems}
                        placeholder="Select a Veterinarian"
                        onSelectItem={(item) => loadStats(item.value)}
                        style={styles.dropdown}
                        dropDownContainerStyle={styles.dropdownList}
                        listMode="SCROLLVIEW"
                    />
                </View>

                <ScrollView 
                    contentContainerStyle={styles.scrollContent} 
                    showsVerticalScrollIndicator={false}
                    scrollEnabled={!open}
                >
                    {statsLoading ? (
                        <ActivityIndicator color="#4CAF50" style={{ marginTop: 50 }} />
                    ) : stats ? (
                        <View>
                            <View style={styles.row}>
                                <View style={styles.miniCard}>
                                    <Text style={styles.miniValue}>{stats.animals || 0}</Text>
                                    <Text style={styles.miniLabel}>Unique Pets</Text>
                                </View>
                                <View style={styles.miniCard}>
                                    <Text style={styles.miniValue}>{stats.total || 0}</Text>
                                    <Text style={styles.miniLabel}>Total Appts</Text>
                                </View>
                            </View>

                            <View style={styles.chartCard}>
                                <Text style={styles.chartHeader}>Appointments Activity</Text>
                                
                                <View style={styles.barGroup}>
                                    <View style={styles.labelRow}>
                                        <Text>Weekly</Text>
                                        <Text style={styles.bold}>{stats.weekly || 0}</Text>
                                    </View>
                                    <View style={styles.barBackground}>
                                        <View style={[
                                            styles.barFill, 
                                            { width: getBarWidth(stats.weekly || 0, stats.total), backgroundColor: '#FFC107' }
                                        ]} />
                                    </View>
                                </View>

                                <View style={styles.barGroup}>
                                    <View style={styles.labelRow}>
                                        <Text>Monthly</Text>
                                        <Text style={styles.bold}>{stats.monthly || 0}</Text>
                                    </View>
                                    <View style={styles.barBackground}>
                                        <View style={[
                                            styles.barFill, 
                                            { width: getBarWidth(stats.monthly || 0, stats.total), backgroundColor: '#00BCD4' }
                                        ]} />
                                    </View>
                                </View>

                                <View style={styles.chartFooter}>
                                    <Text style={styles.footerText}>
                                        The monthly load represents {stats.total > 0 ? Math.round((stats.monthly / stats.total) * 100) : 0}% of total activity.
                                    </Text>
                                </View>
                            </View>
                        </View>
                    ) : (
                        <View style={styles.emptyState}>
                            <Text style={styles.emptyText}>Please select a veterinarian to see their performance data.</Text>
                        </View>
                    )}
                </ScrollView>
            </View>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeArea: { flex: 1, backgroundColor: '#F0F4F0' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    container: { padding: 20, flex: 1 },
    title: { fontSize: 28, fontWeight: 'bold', marginBottom: 20, color: '#2E7D32' },
    dropdownContainer: { marginBottom: 10 },
    dropdown: { borderColor: '#ddd', borderRadius: 12, elevation: 3, backgroundColor: '#fff' },
    dropdownList: { borderColor: '#ddd', backgroundColor: '#fff' },
    scrollContent: { paddingTop: 10, paddingBottom: 30 },
    row: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 20 },
    miniCard: { backgroundColor: '#fff', width: '48%', padding: 20, borderRadius: 20, alignItems: 'center', elevation: 2, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 4 },
    miniValue: { fontSize: 26, fontWeight: 'bold', color: '#2E7D32' },
    miniLabel: { fontSize: 11, color: '#888', textTransform: 'uppercase', marginTop: 4 },
    chartCard: { backgroundColor: '#fff', padding: 20, borderRadius: 25, elevation: 4, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 8 },
    chartHeader: { fontSize: 18, fontWeight: 'bold', marginBottom: 20, color: '#333' },
    barGroup: { marginBottom: 20 },
    labelRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
    bold: { fontWeight: 'bold' },
    barBackground: { height: 12, backgroundColor: '#E0E0E0', borderRadius: 6, overflow: 'hidden' },
    barFill: { height: '100%', borderRadius: 6 },
    chartFooter: { marginTop: 10, borderTopWidth: 1, borderTopColor: '#eee', paddingTop: 15 },
    footerText: { fontSize: 13, color: '#666', fontStyle: 'italic', textAlign: 'center' },
    emptyState: { marginTop: 100, alignItems: 'center', paddingHorizontal: 40 },
    emptyText: { textAlign: 'center', color: '#999', fontSize: 16, lineHeight: 22 }
});